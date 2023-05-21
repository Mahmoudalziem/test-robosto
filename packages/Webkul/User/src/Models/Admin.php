<?php

namespace Webkul\User\Models;

use Webkul\Area\Models\AreaProxy;
use Webkul\Core\Models\AlertProxy;
use Webkul\Admin\Events\MoneyAdded;
use Illuminate\Support\Facades\Storage;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Webkul\Core\Models\AlertAdminProxy;
use Illuminate\Notifications\Notifiable;
use Webkul\Admin\Events\MoneySubtracted;
use Webkul\Core\Models\ActivityLogProxy;
use Webkul\Inventory\Models\WarehouseProxy;
use Webkul\User\Traits\HasPermissionsTrait;
use Illuminate\Database\Eloquent\SoftDeletes;
use Webkul\User\Events\MoneyAddedToAccountant;
use Webkul\Area\Events\AreaManagerPendingMoney;
use Webkul\User\Events\MoneyAddedToAreaManager;
use Webkul\User\Events\MoneyAddedToAreaManagerFromAdjustment;
use Webkul\User\Contracts\Admin as AdminContract;
use Webkul\User\Notifications\AdminResetPassword;
use Webkul\User\Events\MoneySubtractedFromAccountant;
use Webkul\User\Models\AreaManagerTransactionRequest;
use Webkul\User\Events\MoneySubtractedFromAreaManager;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Webkul\Area\Events\AreaManagerPendingMoneyReceived;
use Webkul\Driver\Models\DriverTransactionRequestProxy;
use Webkul\Area\Events\AreaManagerPendingMoneyCancelled;

class Admin extends Authenticatable implements AdminContract, JWTSubject
{
    use Notifiable, HasPermissionsTrait, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'otp',
        'username',
        'id_number',
        'address',
        'image_id',
        'phone_private',
        'phone_work',
        'is_verified',
        'api_token',
        'role_id',
        'status',
    ];
    protected $casts = [
        'is_verified' => 'boolean',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['image_url'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'api_token',
        'remember_token',
        'otp',
    ];

    /**
     * Get all Logs
     */
    public function myLogs()
    {
        return $this->morphMany(ActivityLogProxy::modelClass(), 'causer');
    }
    
    
    /**
     * Get all Logs
     */
    public function logs()
    {
        return $this->morphMany(ActivityLogProxy::modelClass(), 'subject');
    }

    public function deviceToken()
    {
        return $this->hasMany(AdminDeviceTokenProxy::modelClass());
    }

    public function areas()
    {
        return $this->belongsToMany(AreaProxy::modelClass(), 'admin_areas')->withTimestamps();
    }

    public function warehouses()
    {
        return $this->belongsToMany(WarehouseProxy::modelClass(), 'admin_warehouses')->withTimestamps();
    }
    
    public function alertAdmins() {
        return $this->hasMany(AlertAdminProxy::modelClass() ) ;
    }  
    
    public function alerts() {
        return $this->belongsToMany(AlertProxy::modelClass(),'alert_admins' )->withTimestamps()->withPivot('read') ;
    }      

    public function driverTransactions()
    {
        return $this->hasMany(DriverTransactionRequestProxy::modelClass());
    }

    public function areaManagerTransactions()
    {
        return $this->hasMany(AreaManagerTransactionRequestProxy::modelClass(), 'area_manager_id');
    }


    public function accountantTransactions()
    {
        return $this->hasMany(AreaManagerTransactionRequest::modelClass(), 'accountant_id');
    }

    public function areaManagerWallet()
    {
        return $this->hasOne(AreaManagerWalletProxy::modelClass(), 'area_manager_id');
    }

    public function accountantWallet()
    {
        return $this->hasOne(AccountantWalletProxy::modelClass(), 'accountant_id');
    }

    public function tickets()
    {
        return $this->hasMany(TransactionTicketProxy::modelClass(), 'sender_id');
    }

    /**
     * @param float $amount
     */
    public function addMoney(float $amount, string $role = null)
    {
        event(new MoneyAdded($this->id, $amount, $role));
    }

    /**
     * @param float $amount
     */
    public function subtractMoney(float $amount)
    {
        event(new MoneySubtracted($this->id, $amount));
    }

    ////////////////////////////////////////////////////////

    // Area Manager Event Sourcing

    ////////////////////////////////////////////////////

    /**
     * @param float $amount
     * @param int|null $areaId
     * @param int|null $driverId
     * 
     * @return mixed
     */
    public function areaManagerAddMoney(float $amount, int $areaId = null, int $driverId = null)
    {
        event(new MoneyAddedToAreaManager($this->id, $amount, $areaId, $driverId));
    }


    /**
     * @param float $amount
     * @param int|null $driverId
     * @param int|null $areaId
     * 
     * @return mixed
     */
    public function areaManagerPendingMoney(float $amount, int $areaId = null)
    {
        event(new AreaManagerPendingMoney($this->id, $amount, $areaId));
    }

    /**
     * @param float $amount
     * @param int|null $driverId
     * @param int|null $accountantId
     * 
     * @return mixed
     */
    public function areaManagerPendingMoneyReceived(float $amount, int $accountantId = null)
    {
        event(new AreaManagerPendingMoneyReceived($this->id, $amount, $accountantId));
    }
    
    
    /**
     * @param float $amount
     * @param int|null $driverId
     * 
     * @return mixed
     */
    public function areaManagerPendingMoneyCancelled(float $amount)
    {
        event(new AreaManagerPendingMoneyCancelled($this->id, $amount));
    }

    /**
     * @param float $amount
     * @param int|null $areaId
     * @param int|null $accounatntId
     * 
     * @return mixed
     */
    public function areaManagerSubtractMoney(float $amount, int $areaId = null, int $accounatntId = null)
    {
        event(new MoneySubtractedFromAreaManager($this->id, $amount, $areaId, $accounatntId));
    }


    /**
     * @param float $amount
     * @param int|null $areaId
     * @param int|null $driverId
     * 
     * @return mixed
     */
    public function areaManagerAddMoneyFromAjdustment(float $amount, int $areaId = null, int $adjustmentId = null)
    {
        event(new MoneyAddedToAreaManagerFromAdjustment($this->id, $amount, $areaId, $adjustmentId));
    }
    
    ////////////////////////////////////////////////////////

    // Accountant Event Sourcing

    ////////////////////////////////////////////////////

    /**
     * @param float $amount
     * @param int|null $areaId
     * @param int|null $areaManagerId
     * 
     * @return mixed
     */
    public function accountantAddMoney(float $amount, int $areaId = null, int $areaManagerId = null)
    {
        event(new MoneyAddedToAccountant($this->id, $amount, $areaId, $areaManagerId));
    }

    /**
     * @param float $amount
     * @param int|null $areaManagerId
     * @param int|null $accounatntId
     * 
     * @return mixed
     */
    public function accountantSubtractMoney(float $amount)
    {
        event(new MoneySubtractedFromAccountant($this->id, $amount));
    }

    /**
     * Get image url for the category image.
     */
    public function getImageUrlAttribute()
    {
        if (!$this->image)
            return null;

        return Storage::url($this->image);
    }


    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new AdminResetPassword($token));
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
