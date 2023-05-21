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

class ItemsReturnedToWarehouse implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * OrderRepository object
     *
     * @var Order
     */
    public $order;
    /**
     *
     * @array items
     */
    public $items;

    /**
     * Create a new job instance.
     *
     * @param Order $order
     * @param array $items
     */
    public function __construct(Order $order, array $items)
    {
        $this->order = $order;
        $this->items = $items;
    }

    /**
     * Execute the job.
     *
     * @param OrderRepository $orderRepository
     * @return void
     */
    public function handle(OrderRepository $orderRepository)
    {

        $orderRepository->itemsReturnedToWarehouseProcessing($this->order, $this->items);
    }
}
