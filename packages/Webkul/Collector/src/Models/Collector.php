<?php

namespace Webkul\Collector\Models;


use Webkul\Area\Models\AreaProxy;
use Webkul\Sales\Models\OrderProxy;
use Illuminate\Support\Facades\Storage;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Webkul\Core\Models\ActivityLogProxy;
use Webkul\Inventory\Models\WarehouseProxy;
use Webkul\Sales\Models\OrderViolationProxy;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Webkul\Collector\Contracts\Collector as CollectorContract;

class Collector extends Authenticatable  implements  CollectorContract, JWTSubject
{
    use Notifiable, SoftDeletes;

    public const AVAILABILITY_IDLE     = 'online';
    public const AVAILABILITY_OFFLINE    = 'offline';

    public const CAN_RECEIVE_ORDERS     = '1';
    public const CANNOT_RECEIVE_ORDERS  = '0';


    protected $fillable = [
        'area_id',
        'warehouse_id',
        'name',
        'name',
        'username',
        'email',
        'password',
        'address',
        'phone_private',
        'phone_work',
        'id_number',
        'availability',
        'can_receive_orders',
        'is_online',
        'status',
    ];

    protected $hidden = ['password'];

    /**
     * Get all Logs
     */
    public function logs()
    {
        return $this->morphMany(ActivityLogProxy::modelClass(), 'subject');
    }

    public function warehouse()
    {
        return $this->belongsTo(WarehouseProxy::modelClass());
    }

    public function area()
    {
        return $this->belongsTo(AreaProxy::modelClass());
    }

    public function deviceToken()
    {
        return $this->hasMany(CollectorDeviceTokenProxy::modelClass());
    }

    public function orders()
    {
        return $this->hasMany(OrderProxy::modelClass());
    }

    public function loginLogs()
    {
        return $this->hasMany(CollectorLogLoginProxy::modelClass() );
    }

    public function violations()
    {
        return $this->hasMany(OrderViolationProxy::modelClass());
    }

    /**
     * Email exists or not
     */
    public function emailExists($email)
    {
        $results =  $this->where('email', $email);
        if ($results->count() == 0) {
            return false;
        } else {
            return true;
        }
    }

    public function username($username)
    {
        $results =  $this->where('username', $username);
        if ($results->count() == 0) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Get image url for the category image.
     */
    public function image_url()
    {
        if (! $this->image) {
            return;
        }

        return Storage::url($this->image);
    }

    /**
     * Get image url for the category image.
     */
    public function imageIdUrl()
    {
        if (! $this->image_id) {
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
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }
}