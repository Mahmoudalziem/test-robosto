<?php
namespace Webkul\Driver\Services;

use Carbon\Carbon;
use Webkul\Sales\Models\Order;
use Webkul\Driver\Models\Driver;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Webkul\Sales\Models\OrderAddress;
use Webkul\Driver\Models\WorkingCycle;
use Webkul\Driver\Models\WorkingCycleOrder;
use Illuminate\Database\Eloquent\Collection;
use Webkul\Core\Services\LocationService\Distance\DistanceService;

class OrderDelivered 
{
    protected $activeCycle;

    public function __construct(WorkingCycle $activeCycle)
    {
        $this->activeCycle = $activeCycle;
    }

    /**
     * @param Order $order
     * @param Driver $driver
     * 
     * @return void
     */
    public function orderDelivered(Order $order, Driver $driver)
    {
        $activeOrdersCycle = $this->activeCycle->orders;
        
        $currentOrderCycle = $activeOrdersCycle->where('order_id', $order->id)->first();
        if ($currentOrderCycle) {
            
            // Save Current Order Actual Time
            $this->saveOrderActualData($currentOrderCycle);
            
            // Update Next Order
            $this->saveNextOrderActualTime($activeOrdersCycle, $currentOrderCycle->rank);
            
            // Check If this Order is the last order in the cycle
            $this->checkEndOfCycle($activeOrdersCycle, $currentOrderCycle->rank);
        }
    }

    /**
     * @param WorkingCycleOrder $workingCycleOrder
     * 
     * @return void
     */
    private function saveOrderActualData(WorkingCycleOrder $workingCycleOrder)
    {
        $workingCycleOrder->actual_to = now();
        $workingCycleOrder->actual_time = now()->diffInMinutes($workingCycleOrder->actual_from);
        $workingCycleOrder->target = $workingCycleOrder->expected_time - $workingCycleOrder->actual_time;
        $workingCycleOrder->save();
    }
    
    
    /**
     * @param Collection $activeOrdersCycle
     * 
     * @return void
     */
    private function saveNextOrderActualTime(Collection $activeOrdersCycle, int $rank)
    {
        Log::info("Last Rank " . $rank);

        $nextOrder = $activeOrdersCycle->where('rank', $rank + 1)->first();
            
        if ($nextOrder) {
            Log::info("Next Rank " . $nextOrder->rank);
            $nextOrder->actual_from = now();
            $nextOrder->save();
        }
        Log::info("NOOO Next Rank Exist");
    }
    
    
    /**
     * @param Collection $activeOrdersCycle
     * 
     * @return bool
     */
    private function checkEndOfCycle(Collection $activeOrdersCycle, int $rank)
    {
        $nextOrder = $activeOrdersCycle->where('rank', $rank + 1)->first();
        if ($nextOrder) {
            return true;
        }

        Log::info("The Driver Finished his Cycle");
        // Calculate Actual Time For end of cycle
        $this->saveEndOfCycleData();

        // Calculate Expected Back To the warehouse
        $expectedBackData = $this->calculateExpectedBackToWarehouse();
        
        if ($expectedBackData != null) {
            $this->saveExpectedBackTime($expectedBackData);
        }
    }

    /**
     * @return void
     */
    private function saveEndOfCycleData()
    {
        $this->activeCycle->status = WorkingCycle::DONE_STATUS;
        $this->activeCycle->actual_to = now();
        $this->activeCycle->actual_time = now()->diffInMinutes($this->activeCycle->actual_from);
        $this->activeCycle->target = $this->activeCycle->expected_time - $this->activeCycle->actual_time;
        $this->activeCycle->save();

        Event::dispatch('driver.working-path-bonus', $this->activeCycle->driver_id);
    }
    
    
    /**
     * @return null|array
     */
    private function calculateExpectedBackToWarehouse()
    {
        $lastOrder = $this->activeCycle->orders->last();
        $address = OrderAddress::where('order_id', $lastOrder->order_id)->select('id', 'latitude', 'longitude')->first();
        
        $locationsData = [
            'origins'       => [
                ['lat' => $address->latitude,
                'long' => $address->longitude]
            ],
            'dsetinations'  => [
                ['lat' => $lastOrder->warehouse->latitude,
                'long' => $lastOrder->warehouse->longitude]
            ],
        ];

        $response = (new DistanceService())->DistancesBetweenOriginsAndDestinations($locationsData);
        if (isset($response['rows'][0]['elements'][0])) {
            return [
                'time'  => $response['rows'][0]['elements'][0]['duration']['value'],
                'distance' => $response['rows'][0]['elements'][0]['distance']['value']
            ];
        }

        return null;
    }
    
    /**
     * @param array $expectedBackTime
     * 
     * @return void
     */
    private function saveExpectedBackTime(array $expectedBackTime)
    {
        $this->activeCycle->expected_back = now()->addMinutes($expectedBackTime['time']);
        $this->activeCycle->expected_back_distance = $expectedBackTime['distance'];
        $this->activeCycle->save();

        $distanceInKilo = $expectedBackTime['distance'] / 1000;

        if ($distanceInKilo >= config('robosto.DRIVER_MAX_BACK_DISTANCE')) {
            Event::dispatch('driver.on-the-way-back-bonus', $this->activeCycle->driver_id);
        }
    }

}