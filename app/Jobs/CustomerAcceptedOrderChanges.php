<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use LaravelFCM\Message\Exceptions\InvalidOptionsException;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Repositories\OrderRepository;

class CustomerAcceptedOrderChanges implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * OrderRepository object
     *
     * @var Order
     */
    protected $order;

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
        Log::info('Customer Accept Changes Job -> ' . $this->order->id);
        $orderRepository->customerAcceptedChanges($this->order);
    }
}
