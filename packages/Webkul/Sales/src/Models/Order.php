<?php

namespace Webkul\Sales\Models;

use Carbon\Carbon;
use Webkul\Area\Models\AreaProxy;
use Illuminate\Support\Facades\Log;
use Webkul\Core\Models\ChannelProxy;
use Illuminate\Support\Facades\Cache;
use Webkul\Driver\Models\DriverProxy;
use Webkul\Core\Models\ComplaintProxy;
use Webkul\Inventory\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Webkul\Core\Models\ActivityLogProxy;
use Webkul\Customer\Models\CustomerProxy;
use Webkul\Sales\Models\OldOrderItemProxy;
use Webkul\Sales\Models\OrderItemSkuProxy;
use Webkul\Collector\Models\CollectorProxy;
use Webkul\Inventory\Models\WarehouseProxy;
use Webkul\Promotion\Models\PromotionProxy;
use Webkul\Sales\Models\OrderViolationProxy;
use Illuminate\Database\Eloquent\SoftDeletes;
use Webkul\Sales\Models\OldOrderItemSkuProxy;
use Webkul\Sales\Models\OrderCancelReasonProxy;
use Webkul\Customer\Models\CustomerAddressProxy;
use Webkul\Customer\Models\CustomerPaymentProxy;
use Webkul\Sales\Contracts\Order as OrderContract;
use Webkul\Promotion\Models\PromotionVoidDeviceProxy;
use LaravelFCM\Message\Exceptions\InvalidOptionsException;
use Webkul\Customer\Models\BuyNowPayLaterProxy;
use Webkul\Shipping\Models\ShippmentProxy;

class Order extends Model implements OrderContract
{
    use SoftDeletes;

    public const STATUS_SCHEDULED         = 'scheduled';
    public const STATUS_PENDING           = 'pending';
    public const STATUS_WAITING_CUSTOMER_RESPONSE           = 'waiting_customer_response';
    public const STATUS_PREPARING         = 'preparing';
    public const STATUS_READY_TO_PICKUP   = 'ready_to_pickup';
    public const STATUS_ON_THE_WAY        = 'on_the_way';
    public const STATUS_AT_PLACE          = 'at_place';
    public const STATUS_DELIVERED         = 'delivered';
    public const STATUS_RETURNED          = 'returned';
    public const STATUS_CANCELLED          = 'cancelled';
    public const STATUS_CANCELLED_FOR_ITEMS  = 'cancelled_for_items';
    public const STATUS_EMERGENCY_FAILURE  = 'emergency_failure';

    public const ORDER_PAID     = '1';
    public const ORDER_NOT_PAID = '0';

    public const PAID_TYPE_CC       = 'cc';
    public const PAID_TYPE_COD      = 'cod';
    public const PAID_TYPE_BNPL      = 'bnpl';

    protected $fillable = [
        'status',
        'increment_id',
        'customer_id',
        'address_id',
        'channel_id',
        'area_id',
        'warehouse_id',
        'assigned_driver_id',
        'note',
        'is_paid',
        'paid_type',
        'shadow_area_id',
        'shippment_id'
    ];

    protected $appends = [
        'status_name',
        'status_name_for_portal',
        'order_flagged',
        'expected_date',
        'expected_on',
        'expected_delivered_date',
        'delivered_at',
        'cancelled_at',
        'preparing_at',
        'prepared_time',
        'flagged_at',
        'items_found_in_warehuses',
        // 'who_cancelled_order',
        'is_current'
    ];


    /**
     * Get all Logs
     */
    public function logs()
    {
        return $this->morphMany(ActivityLogProxy::modelClass(), 'subject');
    }

    /**
     * Get the order items record associated with the order.
     */
    public function items()
    {
        return $this->hasMany(OrderItemProxy::modelClass());
    }
    
    /**
     * Get the order items record associated with the order.
     */
    public function oldItems()
    {
        return $this->hasMany(OldOrderItemProxy::modelClass());
    }

    /**
     * Get the skus associated with the order item.
     */
    public function skus()
    {
        return $this->hasMany(OrderItemSkuProxy::modelClass(), 'order_id');
    }
    
    /**
     * Get the skus associated with the order item.
     */
    public function oldSkus()
    {
        return $this->hasMany(OldOrderItemSkuProxy::modelClass(), 'order_id');
    }
    
    /**
     * Get the discounts associated with the order item.
     */
    public function itemsDiscounts()
    {
        return $this->hasMany(OrderItemDiscountProxy::modelClass(), 'order_id');
    }

    /**
     * Get the comments record associated with the order.
     */
    public function comment()
    {
        return $this->hasOne(OrderCommentProxy::modelClass());
    }
    
    /**
     * Get the comments record associated with the order.
     */
    public function cancelReason()
    {
        return $this->hasOne(OrderCancelReasonProxy::modelClass());
    }    
    
        /**
     * Get the order notes record associated with the order.
     */
    public function notes()
    {
        return $this->hasMany(OrderNoteProxy::modelClass());
    }



    /**
     * Get the order invoices record associated with the order.
     */
    public function invoices()
    {
        return $this->hasMany(InvoiceProxy::modelClass());
    }

    /**
     * Get the order refunds record associated with the order.
     */
    public function refunds()
    {
        return $this->hasMany(RefundProxy::modelClass());
    }

    /**
     * Get the order refunds record associated with the order.
     */
    public function complaints()
    {
        return $this->hasMany(ComplaintProxy::modelClass());
    }

    /**
     * Get the customer record associated with the order.
     */
    public function customer()
    {
        return $this->belongsTo(CustomerProxy::modelClass());
    }
    
    public function shippment()
    {
        return $this->belongsTo(ShippmentProxy::modelClass());
    }
       

    /**
     * Get the Promotion record associated with the order.
     */
    public function promotion()
    {
        return $this->belongsTo(PromotionProxy::modelClass());
    }
    
    /**
     * Get the Promotion record associated with the order.
     */
    public function promotionDevice()
    {
        return $this->belongsTo(PromotionVoidDeviceProxy::modelClass(),'id','order_id');
    }    

    /**
     * Get the area record associated with the order.
     */
    public function area()
    {
        return $this->belongsTo(AreaProxy::modelClass());
    }

    /**
     * Get the shadow area record associated with the order.
     */
    public function shadowArea()
    {
        return $this->belongsTo(AreaProxy::modelClass(), 'shadow_area_id');
    }

    /**
     * Get the warehouse record associated with the order.
     */
    public function warehouse()
    {
        return $this->belongsTo(WarehouseProxy::modelClass());
    }

    /**
     * Get the driver record associated with the order.
     */
    public function driver()
    {
        return $this->belongsTo(DriverProxy::modelClass());
    }
    
    /**
     * Get the driver record associated with the order.
     */
    public function assignedDriver()
    {
        return $this->belongsTo(DriverProxy::modelClass(), 'assigned_driver_id');
    }

    /**
     * Get the collector record associated with the order.
     */
    public function collector()
    {
        return $this->belongsTo(CollectorProxy::modelClass());
    }


    /**
     * Get the collector record associated with the order.
     */
    public function customerAddress()
    {
        return $this->belongsTo(CustomerAddressProxy::modelClass(), 'address_id');
    }

    /**
     * Get the addresses for the order.
     */
    public function address()
    {
        return $this->hasOne(OrderAddressProxy::modelClass());
    }

    /**
     * Get the payment for the order.
     */
    public function payment()
    {
        return $this->hasOne(OrderPaymentProxy::modelClass());
    }
    
    public function paymentViaCard() {
        return $this->hasOne(CustomerPaymentProxy::modelClass(), 'order_id');
    }    

    /**
     * Get the channel record associated with the order.
     */
    public function channel()
    {
        return $this->belongsTo(ChannelProxy::modelClass());
    }

    /**
     * Get the order logs record associated with the order.
     */
    public function estimatedLogs()
    {
        return $this->hasMany(OrderLogsEstimatedProxy::modelClass());
    }

    /**
     * Get the order logs record associated with the order.
     */
    public function actualLogs()
    {
        return $this->hasMany(OrderLogsActualProxy::modelClass());
    }

    public function violations()
    {
        return $this->hasMany(OrderViolationProxy::modelClass());
    }

    /**
     * Get the order logs record associated with the order.
     */
    public function handleTimeline()
    {
        $logs = [];
        foreach ($this->actualLogs as $log) {
            $logs[] = [
                'title' =>  $log->log_title,
                'key' =>  $log->log_type,
                'date' =>  Carbon::parse($log->log_time)->format('d M Y h:i:s a'),
            ];
        }

        return $logs;
    }

    public function getStatusNameAttribute()
    {
        return $this->handleOrderStatusName($this->status, 'app');
    }


    
    
    public function getStatusNameForPortalAttribute()
    {
        return $this->handleOrderStatusName($this->status);
    }
    
    
    public function handleOrderStatusName($orderStatus, string $environment = 'portal')
    {
        
        $status = '';
        switch ($orderStatus) {
            case self::STATUS_PENDING:
                $status = __('sales::app.pending_order_status');
                break;
            case self::STATUS_PREPARING:
                $status = __('sales::app.preparing_order_status');
                break;
            case self::STATUS_SCHEDULED:
                $status = __('sales::app.scheduled_order_status');
                break;
            case self::STATUS_WAITING_CUSTOMER_RESPONSE:
                $status = __('sales::app.pending_order_status');
                break;
            case self::STATUS_READY_TO_PICKUP:
                $status = __('sales::app.ready_to_pickup_order_status');
                if ($environment == 'app') {
                    $status = __('sales::app.preparing_order_status');
                }
                break;
            case self::STATUS_ON_THE_WAY:
                $status = __('sales::app.on_the_way_order_status');
                break;
            case self::STATUS_AT_PLACE:
                $status = __('sales::app.at_place_order_status');
                break;
            case self::STATUS_DELIVERED:
                $status = __('sales::app.delivered_order_status');
                break;
            case self::STATUS_RETURNED:
                $status = __('sales::app.returned_order_status');
                break;
            case self::STATUS_CANCELLED:
                $status = __('sales::app.cancelled_order_status');
                break;
            case self::STATUS_CANCELLED_FOR_ITEMS:
                $status = __('sales::app.cancelled_order_status');
                break;
        }

        return $status;
    }

    public function getFinalTotalAttribute($value)
    {
        if ($value) {
            $valueExploded = explode('.', $value);
            if (isset($valueExploded[1]) && $valueExploded[1] == 0) {
                return $valueExploded[0];
            }
        }
        return $value;
    }

    public function getOrderFlaggedAttribute()
    {
        if (Cache::has("order_{$this->id}_flagged")) {
            return true;
        }
        return false;
    }

    public function getFlaggedAtAttribute()
    {

        if (in_array($this->status, [self::STATUS_PENDING, self::STATUS_WAITING_CUSTOMER_RESPONSE])) {
            return $this->checkPendingFlagged($this);
        }

        if (in_array($this->status, [self::STATUS_PREPARING, self::STATUS_READY_TO_PICKUP, self::STATUS_ON_THE_WAY, self::STATUS_AT_PLACE])) {
            return  $this->checkActiveFlagged($this);
        }

        if (in_array($this->status, [self::STATUS_DELIVERED, self::STATUS_CANCELLED, self::STATUS_CANCELLED_FOR_ITEMS, self::STATUS_RETURNED])) {
            return  $this->checkHistoryFlagged($this);
        }

        return null;
    }

    /**
     * Handle the event.
     *
     * @param Order $order
     * @return void
     * @throws InvalidOptionsException
     */
    private function checkPendingFlagged($order)
    {
        $pendingOrderTimeBuffer = config('robosto.PENDING_ORDER_BUFFER');

        $totalTime = Carbon::parse($order->created_at)->addMinutes($pendingOrderTimeBuffer)->timestamp;

        return $totalTime;
    }

    /**
     * Handle the event.
     *
     * @param Order $order
     * @return void
     * @throws InvalidOptionsException
     */
    private function checkActiveFlagged($order)
    {
        // Get Prepairing Orders before this order included this order also
        $warehousePendingOrdersQtyShipped = self::where('warehouse_id', $order->warehouse_id)
            ->where('status', self::STATUS_PREPARING)->sum('items_qty_shipped');

        // Caclulate Preparinig Time
        $preparingTimeinSeconds = $warehousePendingOrdersQtyShipped * config('robosto.QAUNTITY_PREPARING_TIME');

        // from driver to customer within warehouse
        $deliveryTime = Cache::get("order_{$order->id}_delivery_time_in_seconds");

        $totalBufferingTimeInSeconds = $preparingTimeinSeconds + $deliveryTime;

        $totalTime = Carbon::parse($order->updated_at)->addSeconds($totalBufferingTimeInSeconds)->timestamp;

        return $totalTime;
    }


    /**
     * Handle the event.
     *
     * @param Order $order
     * @return void
     * @throws InvalidOptionsException
     */
    private function checkHistoryFlagged($order)
    {
        if ($order->expected_on) {
            return $order->expected_on->lt($order->delivered_at);
        }
        return false;
    }

    /**
     * Get Expected Date
     * @return mixed
     */
    public function getExpectedDateAttribute()
    {
        $logTime = $this->estimatedLogs->whereIn('log_type', [OrderLogsEstimated::PREPARING_TIME, OrderLogsEstimated::DELIVERY_TIME]);
        if ($logTime->isNotEmpty()) {
            $times = $logTime->pluck('log_time')->toArray();
            return Carbon::parse($this->created_at)->addSeconds(array_sum($times));
        }
        return null;
    }

    /**
     * Get Expected Date
     * @return mixed
     */
    public function getExpectedOnAttribute()
    {
        $validStatus = [self::STATUS_PENDING, self::STATUS_WAITING_CUSTOMER_RESPONSE];
        if (in_array($this->status, $validStatus)) {
            return null;
        }
        
        $prepairingStatus = [self::STATUS_PREPARING, self::STATUS_READY_TO_PICKUP, self::STATUS_ON_THE_WAY];
        if (in_array($this->status, $prepairingStatus)) {
            return Carbon::parse($this->created_at)->addMinutes(config('robosto.DELIVERY_TIME'));
        }
        
        // if Order is Scheduled
        if ($this->scheduled_at) {
            return Carbon::parse($this->scheduled_at)->addMinutes(config('robosto.DELIVERY_TIME'));
        }

        return Carbon::parse($this->created_at)->addMinutes(config('robosto.DELIVERY_TIME'));
    }

    /**
     * Get Expected Date
     * @return mixed
     */
    public function getExpectedDeliveredDateAttribute()
    {
        $logTime = $this->estimatedLogs->where('log_type', OrderLogsEstimated::DELIVERY_TIME);
        if ($logTime->isNotEmpty()) {
            return $logTime->first()->log_time;
        }
        return null;
    }

    /**
     * Get Expected Date
     * @return mixed
     */
    public function getDeliveredAtAttribute()
    {
        $time = $this->actualLogs->where('log_type', OrderLogsActual::ORDER_DRIVER_ITEMS_DELIVERED)->first();
        return $time ? $time->log_time : null;
    }


    /**
     * Get Cancelled Date
     * @return mixed
     */
    public function getCancelledAtAttribute()
    {
        $time = $this->actualLogs->whereIn('log_type', [OrderLogsActual::ORDER_CUSTOMER_CANCELLED , OrderLogsActual::ORDER_CANCELLED])->first();
        return $time ? $time->log_time : null;
    }

    /**
     * Get Expected Date
     * @return mixed
     */
    public function getPreparingAtAttribute()
    {
        $time = $this->actualLogs->where('log_type', OrderLogsActual::ORDER_ITEMS_PREPARING)->first();
        return $time ? $time->log_time : null;
    }

    /**
     * Get Expected Date
     * @return mixed
     */
    public function getPreparedTimeAttribute()
    {
        $permaringTime = $this->actualLogs->where('log_type', OrderLogsActual::ORDER_ITEMS_PREPARING)->first();
        $permaredTime = $this->actualLogs->where('log_type', OrderLogsActual::ORDER_ITEMS_PREPARED)->first();

        $iSeconds = $permaringTime &&  $permaredTime ?
            ($permaredTime->log_time ? strtotime($permaredTime->log_time) : 0)
            -
            ($permaringTime->log_time ? strtotime($permaringTime->log_time) : 0)
            : 0;
        $min = intval($iSeconds / 60);
        return $min . ':' . str_pad(($iSeconds % 60), 2, '0', STR_PAD_LEFT);
    }


    /**
     * Get Warehouse whichn have items
     * 
     * @return mixed
     */
    public function getItemsFoundInWarehusesAttribute()
    {
        $itemsFounInWarehouses = $this->actualLogs->where('log_type', OrderLogsActual::ORDER_ITEMS_FOUND_IN_WAREHOUSES)->first();

        if ($itemsFounInWarehouses) {
            $warehousesNames = Warehouse::whereIn('id', explode('-', $itemsFounInWarehouses->notes))->get('id');
            return $warehousesNames;
        }

        return [];
    }

    /**
     * Get Admin who cancell the order
     * 
     * @return mixed
     */
    public function getWhoCancelledOrderAttribute()
    {
        $cancelLog = $this->logs->where('action_type', 'order-cancelled')->first();
        if ($cancelLog) {
            return $cancelLog->causer ? $cancelLog->causer->name : '';
        }

        return null;
    }

    public function getIsCurrentAttribute()
    {
        $activeOrders = Cache::get("current_active_orders");
        
        return $activeOrders && is_array($activeOrders) && in_array($this->id, array_values($activeOrders)) ? true : false;
    }


    public function newEloquentBuilder($query)
    {
        return new \Webkul\Core\Eloquent\BaseEloquentBuilder($query);
    }

    public function BNPLTransaction()
    {
        return $this->hasOne(BuyNowPayLaterProxy::modelClass())->where('status',['pending','canceled','released']);
    }

}
