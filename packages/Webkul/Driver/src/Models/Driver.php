<?php

namespace Webkul\Driver\Models;

use Webkul\Motor\Models\Motor;
use Webkul\Sales\Models\Order;
use Webkul\Area\Models\AreaProxy;
use Webkul\Sales\Models\OrderProxy;
use Webkul\Driver\Events\MoneyAdded;
use Illuminate\Support\Facades\Storage;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Webkul\Core\Models\ActivityLogProxy;
use Webkul\Driver\Events\MoneySubtracted;
use Webkul\Inventory\Models\WarehouseProxy;

//use Illuminate\Database\Query\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Webkul\Driver\Contracts\Driver as DriverContract;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Webkul\Driver\Models\DriverTransactionRequestProxy;
use Spatie\EventSourcing\StoredEvents\Models\EloquentStoredEvent;
use Webkul\Sales\Models\OrderViolationProxy;

class Driver extends Authenticatable implements DriverContract, JWTSubject {

    use Notifiable, SoftDeletes;


    protected $table = 'drivers';

    public const CAN_RECEIVE_ORDERS     = '1';
    public const CANNOT_RECEIVE_ORDERS  = '0';

    public const HAS_MULTI_ORDER     = '1';
    public const NOT_HAS_MULTI_ORDER  = '0';

    public const HAS_SHADOW_AREA     = '1';
    public const DEFAULT_DRIVER     = '1';

    public const AVAILABILITY_IDLE = 'idle'; // waiting
    public const AVAILABILITY_DELIVERY = 'delivery'; // on the way
    public const AVAILABILITY_BACK = 'back'; // on the way back
    public const AVAILABILITY_BREAK = 'break';
    public const AVAILABILITY_EMERGENCY = 'emergency';
    public const AVAILABILITY_ONLINE = 'online'; // active
    public const AVAILABILITY_OFFLINE = 'offline';
    public const AVAILABILITY_TRANSACTION = 'transaction';


    protected $fillable = [
        'name',
        'email',
        'password',
        'address',
        'area_id',
        'warehouse_id',
        'phone_private',
        'phone_work',
        'username',
        'id_number',
        'liecese_validity_date',
        'api_token',
        'token',
        'availability',
        'is_online',
        'status',
        'multi_order',
        'can_receive_orders',
        'last_order_date',
        'has_shadow_area',
        'default_driver',
        'max_delivery_orders',
        'wallet',
        'incentive',
        'supervisor_rate'
    ];

    protected $hidden = ['password'];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['image_url', 'on_the_way', 'allowed_logout_date'];
    protected $casts = [
        'allowed_logout_date' => 'date',
//        'status'=> 'boolean',
//        'is_online'=> 'boolean'
    ];

    /**
     * Get all Logs
     */
    public function logs()
    {
        return $this->morphMany(ActivityLogProxy::modelClass(), 'subject');
    }

    public function area() {
        return $this->belongsTo(AreaProxy::modelClass());
    }

    public function warehouse() {
        return $this->belongsTo(WarehouseProxy::modelClass());
    }

    public function transactions() {
        return $this->hasMany(DriverTransactionRequestProxy::modelClass());
    }

    public function motors() {
        return $this->belongsToMany(Motor::class)
                        ->withPivot('motor_condition', 'image', 'status')
                        ->withTimestamps();
    }

    public function statusLogs() {
        return $this->hasMany(DriverStatusLog::class);
    }

    public function loginLogs() {
        return $this->hasMany(DriverLogLoginProxy::modelClass());
    }

    public function breakLogs() {
        return $this->hasMany(DriverLogBreakProxy::modelClass());
    }

    public function emergencyLogs() {
        return $this->hasMany(DriverLogEmergencyProxy::modelClass());
    }

    public function deviceToken() {
        return $this->hasMany(DriverDeviceTokenProxy::modelClass());
    }

    /**
     * Email exists or not
     * @param $email
     * @return bool
     */
    public function emailExists($email) {
        $results = $this->where('email', $email);

        if ($results->count() == 0) {
            return false;
        } else {
            return true;
        }
    }

    public function orders() {
        return $this->hasMany(OrderProxy::modelClass(), 'driver_id', 'id');
    }


    public function activeOrders()
    {
        return $this->hasMany(OrderProxy::modelClass(), 'driver_id')
                    ->whereNotIn('status', [Order::STATUS_CANCELLED, Order::STATUS_CANCELLED_FOR_ITEMS, Order::STATUS_DELIVERED, Order::STATUS_RETURNED]);
    }

    public function getOnTheWayAttribute()
    {
        return $this->activeOrders->whereIn('status', [Order::STATUS_ON_THE_WAY, Order::STATUS_AT_PLACE])->first() ? true : false;
    }

    public function getAllowedLogoutDateAttribute()
    {
        // allowed_logout_date
        $lastLogin = $this->loginLogs()->orderBy('id', 'DESC')->first();
        $driverWorkHours = config('robosto.DRIVER_WORK_HOURS');
        return $lastLogin ? $lastLogin->created_at->addMinutes($driverWorkHours * 60) : null;
    }


    public function supervisorRating()
    {
        return $this->hasMany(SupervisorRatingProxy::modelClass());
    }

    public function violations()
    {
        return $this->hasMany(OrderViolationProxy::modelClass());
    }

    /**
     * @param float $amount
     */
    public function addMoney(float $amount, int $orderId = null, int $orderIncrementId = null) {
        event(new MoneyAdded($this->id, $amount, $orderId, $orderIncrementId));
    }

    /**
     * @param float $amount
     */
    public function subtractMoney(float $amount) {
        event(new MoneySubtracted($this->id, $amount));
    }

    /**
     * @param float $amount
     */
    public function getTotalWallet() {
        return EloquentStoredEvent::query()
                        ->whereEventClass(MoneySubtracted::class)
                        ->where('event_properties->driverId', $this->id)
                        ->latest()->get()->sum('event_properties.amount');
    }

    /**
     * Get image url for the category image.
     */
    public function image_url() {
        if (!$this->image) {
            return;
        }

        return Storage::url($this->image);
    }

    /**
     * Get image url for the category image.
     */
    public function getImageUrlAttribute() {
        if (!$this->image)
            return null;

        return Storage::url($this->image);
    }

    /**
     * Get image url for the category image.
     */
    public function imageIdUrl() {
        if (!$this->image_id) {
            return;
        }

        return Storage::url($this->image_id);
    }

    public function newEloquentBuilder($query) {
        return new \Webkul\Core\Eloquent\BaseEloquentBuilder($query);
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier() {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims() {
        return [];
    }
}