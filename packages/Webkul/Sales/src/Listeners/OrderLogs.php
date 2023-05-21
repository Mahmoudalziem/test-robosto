<?php

namespace Webkul\Sales\Listeners;

use Webkul\Sales\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Webkul\Sales\Repositories\OrderRepository;

class OrderLogs implements ShouldQueue
{
     /**
     * OrderRepository object
     *
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * OrderLogs constructor.
     * @param OrderRepository $orderRepository
     */
    public function __construct(OrderRepository $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }


    /**
     * Handle the event.
     *
     * @param Order $order
     * @param string $logType
     * @return void
     */
    public function updateOrderLogs(Order $order, string $logType, $notes = null)
    {
        $this->orderRepository->storeOrderActualLogs($order, $logType, now()->format('Y-m-d H:i:s'), $notes);
    }

}
