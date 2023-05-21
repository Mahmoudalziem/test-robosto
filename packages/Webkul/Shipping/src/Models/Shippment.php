<?php

namespace Webkul\Shipping\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Webkul\Customer\Models\CustomerAddressProxy;
use Webkul\Inventory\Models\WarehouseProxy;
use Webkul\Sales\Models\OrderProxy;
use Webkul\Shipping\Contracts\Shippment as ShippmentContract;

class Shippment extends Model  implements ShippmentContract
{
    protected $table = 'shippments';
    protected $fillable = ['shipping_number','merchant','shipper_id','area_id','warehouse_id','customer_address_id','first_trial_date','pickup_date','pickup_location_id','shipping_address_id','items_count','final_total','note','failure_reason','current_status','status','description' , 'is_settled','is_rts'];
    protected $appends = [
        'rto_at'
    ];

    public const STATUS_PENDING         = 'pending';
    public const STATUS_SCHEDULED         = 'scheduled';
    public const STATUS_RESCHEDULED         = 'rescheduled';
    public const STATUS_ON_THE_WAY         = 'on_the_way';
    public const STATUS_DELIVERED         = 'delivered';
    public const STATUS_FAILED         = 'failed';

    public const CURRENT_STATUS_PENDING_PICKING_UP_ITEMS         = 'pending_picking_up_items';
    public const CURRENT_STATUS_PENDING_COLLECTING_CUSTOMER_INFO         = 'pending_collecting_customer_info';
    public const CURRENT_STATUS_PENDING_TRANSFER         = 'pending_transfer';
    public const CURRENT_STATUS_PENDING_DISTRIBUTION        = 'pending_distribution';
    public const CURRENT_STATUS_PENDING_DISTRIBUTING        = 'pending_distributing';
    public const CURRENT_STATUS_PENDING_READY_FOR_DISPATCHING         = 'pending_ready_for_dispatching';
    public const CURRENT_STATUS_FAILED_COLLECTING_CUSTOMER_INFO         = 'failed_collecting_customer_info';
    public const CURRENT_STATUS_FAILED_PICKING_UP_ITEMS         = 'failed_picking_up_items';
    public const CURRENT_STATUS_DISPATCHING         = 'dispatching';
    public const CURRENT_STATUS_DELIVERED         = 'delivered';
    public const CURRENT_STATUS_FAILED         = 'failed';
    public const CURRENT_STATUS_RETURNED_TO_VENDOR = 'returned_to_vendor';
    protected static function boot() {
        parent::boot();
        static::created(function($model){
            $shipping_number = 1000000 + $model->id;
            if($model->shipper_id==2){
                $shipping_number = "T-".$shipping_number;
            }
            $model->update(['shipping_number' => $shipping_number]);
        });
    }
    public function shippingAddress()
    {
        return $this->hasOne(ShippingAddressProxy::modelClass(), 'id', 'shipping_address_id');
    }
    public function customerAddress()
    {
        return $this->hasOne(CustomerAddressProxy::modelClass(), 'id', 'customer_address_id');
    }
    public function warehouse()
    {
        return $this->belongsTo(WarehouseProxy::modelClass());
    }

    public function area()
    {
        return $this->belongsTo(WarehouseProxy::modelClass());
    }
    public function shipper()
    {
        return $this->belongsTo(ShipperProxy::modelClass());
    }
    public function pickupLocation()
    {
        return $this->belongsTo(PickupLocationProxy::modelClass());
    }
    public function logs()
    {
        return $this->hasMany(ShippmentLogsProxy::modelClass());
    }
    public function orders()
    {
        return $this->hasMany(OrderProxy::modelClass());
    }
    public function handleTimeline()
    {
        $logs = [];
        foreach ($this->logs as $log) {
            $logs[] = [
                'title' =>  $log->log_title,
                'key' =>  $log->log_type,
                'date' =>  Carbon::parse($log->log_time)->format('d M Y h:i:s a'),
            ];
        }

        return $logs;
    }

    public function getRtoAtAttribute()
    {
        $time = $this->logs->whereIn('log_type', [ShippmentLogs::SHIPPMENT_RETURNED_TO_VENDOR])->first();
        return $time ? $time->log_time : null;
    }
}
