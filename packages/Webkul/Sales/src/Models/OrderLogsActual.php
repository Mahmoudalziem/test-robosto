<?php

namespace Webkul\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Product\Models\ProductProxy;
use Webkul\Sales\Contracts\OrderLogsActual as OrderLogsActualContract;

class OrderLogsActual extends Model implements OrderLogsActualContract
{
    public const ORDER_PLACED           = 'order_placed';
    public const ORDER_ITEMS_FOUND         = 'order_items_found';
    public const ORDER_ITEMS_FOUND_IN_WAREHOUSES         = 'order_items_found_in_warehouses';
    public const ORDER_ITEMS_NOT_FOUND         = 'order_items_not_found';
    public const ORDER_ITEMS_CHANGED         = 'order_items_changed';
    public const ORDER_CUSTOMER_ACCEPTED         = 'order_customer_accepted';
    public const ORDER_CUSTOMER_CANCELLED         = 'order_customer_cancelled';
    public const ORDER_CANCELLED         = 'order_cancelled';
    public const ORDER_DRIVER_ACCEPTED         = 'order_driver_accepted';
    public const ORDER_ITEMS_PREPARING         = 'order_items_preparing';
    public const ORDER_ITEMS_PREPARED         = 'order_items_prepared';
    public const ORDER_DRIVER_ITEMS_CONFIRMED         = 'order_driver_items_confirmed';
    public const ORDER_DRIVER_RETURN_ITEMS_CONFIRMED         = 'order_driver_return_items_confirmed';
    public const ORDER_DRIVER_ITEMS_ON_THE_WAYE       =   'order_driver_items_on_the_way';
    public const ORDER_DRIVER_ITEEMS_AT_PLACE       =   'order_driver_items_at_placed';
    public const ORDER_DRIVER_ITEMS_DELIVERED       =   'order_driver_items_delivered';
    public const ORDER_CUSTOMER_DELIVERED       =   'order_customer_delivered';
    public const ORDER_CUSTOMER_RATING       =   'order_customer_rating';
    public const ORDER_CUSTOMER_RETURNED       =   'order_customer_returned';
    public const ORDER_RETURNED_TO_WAREHOUSE       =   'order_retuned_to_warehouse';
    public const ORDER_CUSTOMER_UPDATED       =   'order_customer_updated';
    public const ORDER_DRIVER_RETURN_BACK       =   'order_driver_return_back';
    public const ORDER_CUSTOMER_RETURN_REQUEST       =   'order_customer_return_request';
    public const ORDER_DRIVER_RETURN_WITH_ITEMS       =   'order_driver_return_with_items';
    public const ORDER_ITEMS_PICKED_UP       =   'order_items_picked_up';
    public const ORDER_ITEMS_INVENTORIED       =   'order_items_inventoried';
    public const ORDER_REDISPATCH       =   'order_redispatch';


    protected $table = 'order_logs_actual';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'aggregator_id',
        'log_type',
        'log_time',
        'notes'
    ];

    protected $appends = ['log_title'];

    public function getLogTitleAttribute()
    {
        if ($this->log_type == self::ORDER_PLACED) {
            return 'Order placed By ';

        } elseif ($this->log_type == self::ORDER_ITEMS_FOUND) {
            return 'Items Found';

        } elseif ($this->log_type == self::ORDER_ITEMS_FOUND_IN_WAREHOUSES) {
            return 'Items Found';

        } elseif ($this->log_type == self::ORDER_ITEMS_NOT_FOUND) {
            return 'Items not found ';

        } elseif ($this->log_type == self::ORDER_ITEMS_CHANGED) {
            return 'Order changes ';

        } elseif ($this->log_type == self::ORDER_CUSTOMER_ACCEPTED) {
            return 'Customer cofirmation';

        } elseif ($this->log_type == self::ORDER_CUSTOMER_CANCELLED || $this->log_type == self::ORDER_CANCELLED) {
            return 'Order cancelation';

        } elseif ($this->log_type == self::ORDER_DRIVER_ACCEPTED) {
            return 'Assigned driver';

        } elseif ($this->log_type == self::ORDER_ITEMS_PREPARING) {
            return 'Preparing';

        } elseif ($this->log_type == self::ORDER_ITEMS_PREPARED) {
            return 'Prepared';

        } elseif ($this->log_type == self::ORDER_DRIVER_ITEMS_CONFIRMED) {
            return 'Driver order confirmation';

        } elseif ($this->log_type == self::ORDER_DRIVER_RETURN_ITEMS_CONFIRMED) {
            return 'Driver return confirmation/customer';

        } elseif ($this->log_type == self::ORDER_DRIVER_ITEMS_ON_THE_WAYE) {
            return 'On the way';

        } elseif ($this->log_type == self::ORDER_DRIVER_ITEEMS_AT_PLACE) {
            return 'At place';

        } elseif ($this->log_type == self::ORDER_DRIVER_ITEMS_DELIVERED) {
            return 'Delivered';

        } elseif ($this->log_type == self::ORDER_CUSTOMER_RATING) {
            return 'Customer review';

        } elseif ($this->log_type == self::ORDER_CUSTOMER_RETURNED) {
            return 'Order returned';

        } elseif ($this->log_type == self::ORDER_RETURNED_TO_WAREHOUSE) {
            return 'Stock update';

        } elseif ($this->log_type == self::ORDER_CUSTOMER_UPDATED) {
            return 'Customer Request Edit the order';

        } elseif ($this->log_type == self::ORDER_DRIVER_RETURN_BACK) {
            return 'Driver return to warehouse';

        } elseif ($this->log_type == self::ORDER_CUSTOMER_RETURN_REQUEST) {
            return 'Customer Request Return the order';

        } elseif ($this->log_type == self::ORDER_DRIVER_RETURN_WITH_ITEMS) {
            return 'Driver return with items to warehouse';

        } elseif ($this->log_type == self::ORDER_ITEMS_PICKED_UP) {
            return 'Collector confirm returned items';

        } elseif ($this->log_type == self::ORDER_REDISPATCH) {
            return 'Redispatch Order';
        } else {
            return 'Order changes ';
        }
    }

    /**
     * Get the order record associated with the address.
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}