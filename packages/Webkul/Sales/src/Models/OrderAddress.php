<?php

namespace Webkul\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Sales\Contracts\OrderAddress as OrderAddressContract;

class OrderAddress extends Model implements OrderAddressContract
{

    protected $table = 'order_address';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    protected $fillable = [
        'name',
        'address',
        'floor_no',
        'apartment_no',
        'building_no',
        'landmark',
        'latitude',
        'longitude',
        'phone',
        'order_id',
    ];

    public function getPhoneAttribute($phone)
    {
        // Temprarry, Return Real phone Until fix innocalls Issues
        return $phone;
        if (auth('driver')->check()) {
            return config('robosto.ROBOSTO_PHONE');
        }

        return $phone;
    }

    /**
     * Get the order record associated with the address.
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

}