<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Webkul\Sales\Models\Order;
use Webkul\Driver\Models\Driver;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Webkul\Sales\Repositories\OrderRepository;
use LaravelFCM\Message\Exceptions\InvalidOptionsException;

class DriverAcceptedNewOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Driver
     *
     * @var Driver
     */
    protected $driver;

    /**
     * Order object
     *
     * @var Order
     */
    public $order;


    /**
     * Create a new job instance.
     *
     * @param Order $order
     * @param Driver $driver
     */
    public function __construct(Order $order, Driver $driver)
    {
        $this->order = $order;
        $this->driver = $driver;
    }

    /**
     * Execute the job.
     *
     * @param OrderRepository $orderRepository
     * @return void
     * @throws InvalidOptionsException
     */
    public function handle(OrderRepository $orderRepository)
    {
        Log::info('Start Order Driver Accept Order -> ' . $this->order->id);
        $orderRepository->driverAcceptedNewOrder($this->order, $this->driver);
    }
}
