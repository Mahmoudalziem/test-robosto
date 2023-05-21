<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Webkul\Sales\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Webkul\Driver\Services\CallDriver;
use LaravelFCM\Message\Exceptions\InvalidOptionsException;
use Webkul\Driver\Repositories\DriverRepository;
use Webkul\Sales\Repositories\OrderRepository;

class CallDriverJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const ORDER_AT_PLACE_TYPE = 'order_at_place';

    /**
     * Order object
     *
     * @var int
     */
    public $orderId;

    /**
     * int object
     *
     * @var int
     */
    public $driverId;


    /**
     *
     * @var string
     */
    public $type;

    /**
     * Create a new job instance.
     *
     * @param array $driverId
     */
    public function __construct(int $driverId, int $orderId = null, string $type = null)
    {
        $this->orderId = $orderId;
        $this->driverId = $driverId;
        $this->type = $type;
    }

    /**
     * Execute the job.
     *
     * @param CallDriver $callDriver
     * @param OrderRepository $orderRepository
     * @param DriverRepository $driverRepository
     * 
     * @return void
     * @throws InvalidOptionsException
     */
    public function handle(CallDriver $callDriver, OrderRepository $orderRepository, DriverRepository $driverRepository)
    {
        $order = null;
        $driver = $driverRepository->find($this->driverId);
        
        if ($this->orderId) {
            $order = $orderRepository->find($this->orderId);
        }

        Log::info('>> Start Call Driver Job -> ' . $driver->id);

        if ($this->type == self::ORDER_AT_PLACE_TYPE) {
            Log::info("Call becuase of order still at place");
            $callDriver->orderAtPlaceCall($driver, $order);
        }

    }
}
