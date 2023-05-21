<?php

namespace Webkul\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Product\Models\ProductProxy;
use Webkul\Sales\Contracts\OrderLogsEstimated as OrderLogsEstimatedContract;

class OrderLogsEstimated extends Model implements OrderLogsEstimatedContract
{
    public const PREPARING_TIME         = 'preparing_time';
    public const DELIVERY_TIME         = 'delivery_time';

    protected $table = 'order_logs_estimated';

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
    ];

    /**
     * Get the order record associated with the address.
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}