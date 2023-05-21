<?php
namespace Webkul\Driver\Services;

use Carbon\Carbon;
use Webkul\Sales\Models\Order;
use Webkul\Driver\Models\Driver;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Event;
use Webkul\Sales\Models\OrderAddress;
use Webkul\Driver\Models\WorkingCycle;
use Webkul\Driver\Models\WorkingCycleOrder;
use Illuminate\Database\Eloquent\Collection;
use Webkul\Core\Services\LocationService\Distance\DistanceService;

class OrderCancelled 
{
    protected $activeOrder;
    protected $activeCycle;
    protected $ordersCycleToUpdate;

    /**
     * @param Order $order
     * 
     * @return void|bool
     */
    public function orderCancelled(Order $order)
    {
        $this->activeOrder = WorkingCycleOrder::where('order_id', $order->id)->first();

        if (!$this->activeOrder) return true;

        $this->activeCycle = $this->activeOrder->cycle;
        $this->ordersCycleToUpdate = WorkingCycleOrder::where('working_cycle_id', $this->activeCycle->id)->where('rank', '>', $this->activeOrder->rank)->get();
        
        // Update Main Cycle 
        $this->updateRemainingOrders();

        // Update All Orders which comes after the cancelled order
        $this->updateMainCycle();

        // Check If this Order is the last order in the cycle
        $this->checkEndOfCycle($this->activeCycle->orders, $this->activeOrder->rank);

        // Update Main Cycle 
        $this->updateRemainingOrdersRanks();

        // Delete the cancelled order from the cycle
        $this->deleteCancelledOrder();
    }

    /**
     * @return void
     */
    private function updateRemainingOrders()
    {
        foreach ($this->ordersCycleToUpdate as $order) {
            
            $order->expected_from = Carbon::parse($order->expected_from)->subMinutes($this->activeOrder->expected_time);
            $order->expected_to = Carbon::parse($order->expected_to)->subMinutes($this->activeOrder->expected_time);
            $order->save();
        }
    }

    /**
     * @return void
     */
    private function updateMainCycle()
    {
        $updatedPath = $this->updateCyclePath();
        
        $this->activeCycle->expected_to = Carbon::parse($this->activeCycle->expected_to)->subMinutes($this->activeOrder->expected_time);
        $this->activeCycle->expected_time -= $this->activeOrder->expected_time;
        $this->activeCycle->path = json_encode($updatedPath);
        $this->activeCycle->save();
    }
    
    
    /**
     * @return array
     */
    private function updateCyclePath()
    {
        $pathCollection = json_decode($this->activeCycle->path, true);
        foreach ($pathCollection as $key => $value) {
            if ($value['order_id'] == $this->activeOrder->order_id) {
                unset($pathCollection[$key]);
            }
        }

        return array_values($pathCollection);
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
                [
                    'lat' => $address->latitude,
                    'long' => $address->longitude
                ]
            ],
            'dsetinations'  => [
                [
                    'lat' => $lastOrder->warehouse->latitude,
                    'long' => $lastOrder->warehouse->longitude
                ]
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

    /**
     * @return void
     */
    private function updateRemainingOrdersRanks()
    {
        foreach ($this->ordersCycleToUpdate as $order) {
            $order->rank = $order->rank - 1;
            $order->save();
        }
    }
    
    /**
     * @return void
     */
    private function deleteCancelledOrder()
    {
        $this->activeOrder->delete();
    }

}