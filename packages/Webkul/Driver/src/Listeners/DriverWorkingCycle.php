<?php

namespace Webkul\Driver\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Webkul\Driver\Repositories\WorkingCycleRepository;
use Webkul\Sales\Models\Order;

class DriverWorkingCycle implements ShouldQueue
{
    protected $workingCycleRepository;

    /**
     * @param WorkingCycleRepository $workingCycleRepository
     * 
     * @return void
     */
    public function __construct(WorkingCycleRepository $workingCycleRepository)
    {
        $this->workingCycleRepository = $workingCycleRepository;
    }

    /**
     * Handle the event.
     *
     * @param  int  $orderId
     * @return void
     */
    public function newOrderAssigned(int $orderId)
    {
        
        Log::info('New Order Assigned event fired');

        $this->workingCycleRepository->newOrderAssigned($orderId);
    }


    /**
     * Handle the event.
     *
     * @param int $driverId
     * @return void
     */
    public function startDelivery(int $driverId)
    {
        Log::info('Driver Start Delivery event fired');

        $this->workingCycleRepository->driverStartDelivery($driverId);
    }


    /**
     * Handle the event.
     *
     * @param int $orderId
     * @return void
     */
    public function orderDelivered(int $orderId)
    {
        Log::info('Order Delivered event fired');

        $this->workingCycleRepository->orderDelivered($orderId);
    }
    
    
    /**
     * Handle the event.
     *
     * @param int $orderId
     * @return void
     */
    public function orderCancelled(int $orderId)
    {
        Log::info('Order Cancelled event fired');

        $this->workingCycleRepository->orderCancelled($orderId);
    }
    
    public function testListner(int $count)
    {
        Log::info('Test Event fired . ' . $count);
    }
}
