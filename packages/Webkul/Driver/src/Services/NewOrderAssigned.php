<?php
namespace Webkul\Driver\Services;

use Carbon\Carbon;
use Webkul\Sales\Models\Order;
use Webkul\Driver\Models\Driver;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Webkul\Driver\Models\WorkingCycle;
use Webkul\Driver\Repositories\WorkingCycleRepository;

class NewOrderAssigned 
{
    protected $workingCycleRepository;

    public function __construct(WorkingCycleRepository $workingCycleRepository)
    {
        $this->workingCycleRepository = $workingCycleRepository;
    }

    /**
     * @param Order $order
     * @param Driver $driver
     * 
     * @return void
     */
    public function createNewCycle(Order $order, Driver $driver)
    {
        Log::info("Create New Cycle For The Driver  " . $driver->id);

        $nextExpectedCycleTime = $this->getNextCycleExpectedFrom($order, $driver);

        $data['expected_from'] = $nextExpectedCycleTime;
        $data['driver_id'] = $driver->id;
        $data['area_id'] = $driver->area_id;
        $data['warehouse_id'] = $driver->warehouse_id;

        // Create New Cycle
        $this->workingCycleRepository->create($data);
    }

    /**
     * @param WorkingCycle $activeCycle
     * 
     * @return void
     */
    public function updateActiveCycle(WorkingCycle $activeCycle)
    {
        $activeCycle->expected_from = Carbon::parse($activeCycle->expected_from)->addMinutes(config('robosto.EXPECTED_TIME_FOR_PREPARING_ORDER', 5));
        // $activeCycle->expected_from = now()->addMinutes(config('robosto.EXPECTED_TIME_FOR_PREPARING_ORDER', 5));
        $activeCycle->save();
    }

    /**
     * @param WorkingCycle $activeCycle
     * @param Order $order
     * @param Driver $driver
     * 
     * @return void
     */
    private function getNextCycleExpectedFrom(Order $order, Driver $driver)
    {
        // Get Last Active Cycle Today
        $lastActiveCycle = WorkingCycle::where('status', WorkingCycle::DONE_STATUS)->whereDate('created_at', now())->latest()->first();
        if ($lastActiveCycle) {

            // If the Order Assigned after the driver reached to warehouse
            if ($lastActiveCycle->expected_back < now()) {
                return now()->addMinutes(config('robosto.EXPECTED_TIME_FOR_PREPARING_ORDER', 5));
            }

            return Carbon::parse($lastActiveCycle->expected_back)->addMinutes(config('robosto.EXPECTED_TIME_FOR_PREPARING_ORDER', 5));
        }

        return now()->addMinutes(config('robosto.EXPECTED_TIME_FOR_PREPARING_ORDER', 5));
    }
}