<?php

namespace Webkul\Customer\Models;

use Webkul\Area\Models\Area;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Webkul\Customer\Contracts\CustomerAddress as CustomerAddressContract;

class CustomerAddress extends Model implements CustomerAddressContract
{
    use SoftDeletes;

    protected $table = 'customer_addresses';

    protected $fillable = [
        'customer_id',
        'area_id',
        'icon_id',
        'name',
        'building_no',
        'floor_no',
        'apartment_no',
        'landmark',
        'latitude',
        'longitude',
        'address',
        'phone',
        'is_default',
        'covered'
    ];

    protected $with = ['icon'];

    /**
     * @var array default values
     */
    protected $attributes = [

    ];

    protected $appends=['area_name'];

    public function area()
    {
        return $this->belongsTo(Area::class, 'area_id');
    }

    public function icon()
    {
        return $this->belongsTo(AddressIcon::class, 'icon_id');
    }

    public function getAreaNameAttribute(){
        return Area::find($this->area_id)->name;
    }

    /**
     * Scope a query to only include active categories.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeCovered(Builder $query)
    {
        return $query->where('covered', '1')->whereIn('area_id',[1,3]);
    }
}
