<?php

namespace Webkul\Shipping\Models;

use Illuminate\Support\Facades\Storage;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Webkul\Customer\Models\CustomerProxy;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Webkul\Shipping\Contracts\Shipper as ShipperContract;

class Shipper extends Authenticatable implements ShipperContract, JWTSubject
{
    protected $table = 'shippers';

    protected $fillable = ['name', 'email', 'password', 'logo', 'address', 'phone_work', 'phone_private', 'status'];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['logo_url'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token'
    ];

    public function pickupLocation(){
        return $this->hasMany(PickupLocationProxy::modelClass());
    }
    public function shippments(){
        return $this->hasMany(ShippmentProxy::modelClass());
    }
    public function shippingAddress(){
        return $this->hasMany(ShippingAddressProxy::modelClass());
    }


    public function getLogoUrlAttribute()
    {
        if (!$this->logo)
            return null;

        return Storage::url($this->logo);
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
