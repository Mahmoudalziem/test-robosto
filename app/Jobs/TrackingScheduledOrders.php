<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Repositories\OrderRepository;

class TrackingScheduledOrders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * OrderRepository object
     *
     * @var Order
     */
    public $order;

    /**
     * Create a new job instance.
     *
     * @param Order $order
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Execute the job.
     *
     * @param OrderRepository $orderRepository
     * @return void
     */
    public function handle(OrderRepository $orderRepository)
    {
        if ($this->order->status != Order::STATUS_SCHEDULED) {
            return false;
        }

        $this->order->status=Order::STATUS_PENDING;
        $this->order->save();
        if($this->order->shippment_id){
            if($this->order->customer_id){
                $orderRepository->acceptShippingOrderForDriverAndCollector($this->order, false);
            }else{
                $orderRepository->acceptShippingOrderForDriverAndCollector($this->order, true);
            }
        }else{
            $orderRepository->orderProcessing($this->order);
        }
    }
}
