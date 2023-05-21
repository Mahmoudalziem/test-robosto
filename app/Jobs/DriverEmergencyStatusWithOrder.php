<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Webkul\Driver\Repositories\DriverRepository;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Repositories\AggregateOrderRepository;
use Webkul\Sales\Repositories\OrderRepository;

class DriverEmergencyStatusWithOrder implements ShouldQueue
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
     * @param AggregateOrderRepository $aggregateOrderRepository
     * @return void
     * @throws \Exception
     */
    public function handle(AggregateOrderRepository $aggregateOrderRepository)
    {
        Log::info('Start Aggregate Order Processing Job -> ' . $this->order->id);
        $aggregateOrderRepository->placeAggregateOrder($this->order);
    }
}
