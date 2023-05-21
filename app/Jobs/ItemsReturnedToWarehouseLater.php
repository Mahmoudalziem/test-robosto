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

class ItemsReturnedToWarehouseLater implements ShouldQueue
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

    public $inventoryAdjustment;

    /**
     * Create a new job instance.
     *
     * @param Order $order
     * @param array $items
     */
    public function __construct(Order $order,$inventoryAdjustment, array $items)
    {
        $this->order = $order;
        $this->items = $items;
        $this->inventoryAdjustment=$inventoryAdjustment;
    }

    /**
     * Execute the job.
     *
     * @param OrderRepository $orderRepository
     * @return void
     */
    public function handle(OrderRepository $orderRepository)
    {

        $orderRepository->itemsReturnedToWarehouseLaterProcessing($this->order,$this->inventoryAdjustment, $this->items);
    }
}
