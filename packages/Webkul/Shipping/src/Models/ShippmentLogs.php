<?php

namespace Webkul\Shipping\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Shipping\Contracts\ShippmentLogs as ShippmentLogsContract;

class ShippmentLogs extends Model implements ShippmentLogsContract
{
    public const SHIPPMENT_CREATED         = 'shippment_created';
    public const SHIPPMENT_PICK_UP_ORDER_CREATED         = 'picking_up_order_created';
    public const SHIPPMENT_PICK_UP_ORDER_ON_THE_WAY         = 'pick_up_order_on_the_way';
    public const SHIPPMENT_PICK_UP_ORDER_FAILED         = 'picking_up_order_failed';
    public const SHIPPMENT_ITEMS_PICKED_UP         = 'items_picked_up';
    public const SHIPPMENT_PENDING_COLLECTING_CUSTOMER_INFO         = 'pending_collecting_customer_info';
    public const SHIPPMENT_COLLECTED_CUSTOMER_INFO         = 'collected_customer_info';
    public const SHIPPMENT_FAILED_COLLECTING_CUSTOMER_INFO         = 'failed_collecting_customer_info';

    public const SHIPPMENT_PENDING_DISTRIBUTION        = 'pending_distribution';
    public const SHIPPMENT_DISTRIBUTION_STARTED        = 'distributing';
    public const SHIPPMENT_DISTRIBUTION_ON_THE_WAY         = 'distribution_on_the_way';
    public const SHIPPMENT_DISTRIBUTION_CANCELLED          = 'distribution_cancelled';
    public const SHIPPMENT_DISTRIBUTION_DELIVERED          = 'distribution_delivered';

    public const SHIPPMENT_PENDING_TRANSFER         = 'pending_transfer';
    public const SHIPPMENT_TRANSFER_ON_THE_WAY         = 'transfer_on_the_way';
    public const SHIPPMENT_TRANSFERED         = 'transfered';
    public const SHIPPMENT_DISPATCHING         = 'dispatching';
    public const SHIPPMENT_TRIAL_CREATED         = 'shippment_trial_created';
    public const SHIPPMENT_TRIAL_ON_THE_WAY         = 'shippment_trial_on_the_way';
    public const SHIPPMENT_TRIAL_FAILED         = 'shippment_trial_failed';
    public const SHIPPMENT_TRIAL_RESCHEDULED         = 'shippment_trial_rescheduled';
    public const SHIPPMENT_DELIVERED         = 'delivered';
    public const SHIPPMENT_FAILED         = 'failed';
    public const SHIPPMENT_RETURNED_TO_VENDOR         = 'returned_to_vendor';

    protected $table = 'shippments_logs';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    protected $fillable = [
        'shippment_id',
        'log_type',
        'log_time',
        'notes'
    ];

    protected $appends = ['log_title'];

    public function getLogTitleAttribute()
    {
        if ($this->log_type == self::SHIPPMENT_CREATED) {
            return 'Shipment Created';

        } elseif ($this->log_type == self::SHIPPMENT_PICK_UP_ORDER_CREATED) {
            return 'Pickup Order Created';

        } elseif ($this->log_type == self::SHIPPMENT_PICK_UP_ORDER_ON_THE_WAY) {
            return 'Pickup Order On The Way';

        } elseif ($this->log_type == self::SHIPPMENT_PICK_UP_ORDER_FAILED) {
            return 'Pickup Order Failed';

        } elseif ($this->log_type == self::SHIPPMENT_ITEMS_PICKED_UP) {
            return 'Items Picked up';

        } elseif ($this->log_type == self::SHIPPMENT_PENDING_COLLECTING_CUSTOMER_INFO) {
            return 'Collecting Customer Location Info';

        } elseif ($this->log_type == self::SHIPPMENT_COLLECTED_CUSTOMER_INFO) {
            return 'Customer info collected';

        }elseif ($this->log_type == self::SHIPPMENT_FAILED_COLLECTING_CUSTOMER_INFO) {
            return 'Failed to collect customer info';

        } elseif ($this->log_type == self::SHIPPMENT_PENDING_TRANSFER) {
            return 'Pending items transferring';

        } elseif ($this->log_type == self::SHIPPMENT_TRANSFER_ON_THE_WAY) {
            return 'Items on the way to dispatching warehouse';

        } elseif ($this->log_type == self::SHIPPMENT_TRANSFERED) {
            return 'Items delivered to dispatching warehouse';

        } elseif ($this->log_type == self::SHIPPMENT_DISPATCHING) {
            return 'Dispathcing Shipment';

        } elseif ($this->log_type == self::SHIPPMENT_TRIAL_CREATED) {
            return 'Shipping trial started';

        } elseif ($this->log_type == self::SHIPPMENT_TRIAL_ON_THE_WAY) {
            return 'Shipment on the way to customer';

        } elseif ($this->log_type == self::SHIPPMENT_TRIAL_FAILED) {
            return 'Failed Delivering shipment trial';

        } elseif ($this->log_type == self::SHIPPMENT_DELIVERED) {
            return 'Shipment Delivered';

        } elseif ($this->log_type == self::SHIPPMENT_FAILED) {
            return 'Failed Delivering shipment';
        }
        elseif ($this->log_type == self::SHIPPMENT_PENDING_DISTRIBUTION) {
            return 'Pending Distributing Shipment';
        } elseif ($this->log_type == self::SHIPPMENT_DISTRIBUTION_STARTED) {
            return 'Distributing Shipment';
        } elseif ($this->log_type == self::SHIPPMENT_DISTRIBUTION_ON_THE_WAY) {
            return 'Shipment Distribution On The Way';
        } elseif ($this->log_type == self::SHIPPMENT_DISTRIBUTION_CANCELLED) {
            return 'Shipment Distribution Cancelled';
        } elseif ($this->log_type == self::SHIPPMENT_DISTRIBUTION_DELIVERED) {
            return 'Shipment Distribution Delivered';
        }elseif ($this->log_type == self::SHIPPMENT_RETURNED_TO_VENDOR) {
            return 'Shipment Is Back To Shipper';
        }
        
    }
    public function shippment()
    {
        return $this->belongsTo(ShippmentProxy::modelClass());
    }
}