<?php

namespace Webkul\Sales\Providers;

use Konekt\Concord\BaseModuleServiceProvider;

class ModuleServiceProvider extends BaseModuleServiceProvider
{
    protected $models = [
        \Webkul\Sales\Models\Order::class,
        \Webkul\Sales\Models\OrderItem::class,
        \Webkul\Sales\Models\OrderItemSku::class,
        \Webkul\Sales\Models\OrderAddress::class,
        \Webkul\Sales\Models\OrderPayment::class,
        \Webkul\Sales\Models\OrderComment::class,
        \Webkul\Sales\Models\PaymentMethod::class,
        \Webkul\Sales\Models\Invoice::class,
        \Webkul\Sales\Models\InvoiceItem::class,
        \Webkul\Sales\Models\Refund::class,
        \Webkul\Sales\Models\RefundItem::class,
        \Webkul\Sales\Models\OrderLogsEstimated::class,
        \Webkul\Sales\Models\OrderLogsActual::class,
        \Webkul\Sales\Models\OrderNote::class,
        \Webkul\Sales\Models\OldOrderItem::class,
        \Webkul\Sales\Models\OldOrderItemSku::class,
        \Webkul\Sales\Models\OrderCancelReason::class,
        \Webkul\Sales\Models\OrderViolation::class
    ];
}