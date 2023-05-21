<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use LaravelFCM\Message\Exceptions\InvalidOptionsException;
use Webkul\Driver\Models\Driver;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Repositories\OrderRepository;

class DriverRejectedNewOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * OrderRepository object
     *
     * @var Order
     */
    protected $order;

    /**
     * Driver
     *
     * @var Driver
     */
    protected $driver;

    protected $reason;

    /**
     * Create a new job instance.
     *
     * @param Order $order
     * @param Driver $driver
     * @param string $reason
     */
    public function __construct(Order $order, Driver $driver, string $reason)
    {
        $this->order = $order;
        $this->driver = $driver;
        $this->reason = $reason;
    }

    /**
     * Execute the job.
     *
     * @param OrderRepository $orderRepository
     * @return void
     */
    public function handle(OrderRepository $orderRepository)
    {
        Log::info('Start Driver Reject Order Job -> ' . $this->order->id);
        $orderRepository->driverRejectedNewOrder($this->order, $this->driver, $this->reason);
    }
}
