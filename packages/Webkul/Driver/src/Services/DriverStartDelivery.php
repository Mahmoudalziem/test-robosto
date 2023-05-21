<?php
namespace Webkul\Driver\Services;

use Carbon\Carbon;
use Webkul\Sales\Models\Order;
use Webkul\Driver\Models\Driver;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Webkul\Driver\Models\WorkingCycle;
use Webkul\Driver\Repositories\WorkingCycleRepository;
use Webkul\Core\Services\LocationService\Distance\DistanceService;

class DriverStartDelivery 
{
    protected $workingCycleRepository;
    protected $activeCycle;
    protected $roadPath;

    public function __construct(WorkingCycleRepository $workingCycleRepository)
    {
        $this->workingCycleRepository = $workingCycleRepository;
    }

    /**
     * @param array $warehouse
     * @param array $addresses
     * 
     * @return void
     */
    public function calculateAndSaveCycle(WorkingCycle $activeCycle, array $warehouse, array $addresses)
    {
        $this->activeCycle = $activeCycle;
        $roadPath = $this->calculatingProcess($warehouse, $addresses);

        $this->saveRoadPath($roadPath);
    }
    
    
    /**
     * @param array $warehouse
     * @param array $addresses
     * 
     * @return array
     */
    private function calculatingProcess(array $warehouse, array $addresses)
    {
        $locationsData = $this->prepareOriginsAndDestinations($warehouse, $addresses);

        $response = (new DistanceService())->DistancesBetweenOriginsAndDestinations($locationsData);

        if ($response == false) {
            // Distance Failover
            return true;
        }

        return (new DistanceResponseHandling($this->activeCycle))->handleResponse($response, $locationsData);
    }

    /**
     * @param array $path
     * 
     * @return void
     */
    private function saveRoadPath(array $path)
    {
        $this->roadPath = $path;

        $total = $this->getTotalPathTimeAndDistance();

        $this->activeCycle->expected_to = Carbon::parse($this->activeCycle->expected_from)->addMinutes($total['time']);
        $this->activeCycle->expected_time = $total['time'];
        $this->activeCycle->distance = $total['distance'];
        $this->activeCycle->actual_from = now();
        $this->activeCycle->path = json_encode($path);        
        $this->activeCycle->save();

        // Save The Path for each order
        $this->saveActiveCycleOrdersPath();

        // Save The Expected Back Time from Last Order to warehouse
        // $this->saveExpectedBack();

        // Save Actual Time from The First Order from warehouse
        $this->saveFirstOrderActualTime();
    }

    /**
     * 
     * @return void
     */
    private function saveActiveCycleOrdersPath()
    {
        $currentTime = 0;
        $rank = 1;
        foreach ($this->roadPath as $item) {

            if (isset($item['order_id'])) {
                $time = ceil($item['time'] / 60);
                $data = [
                    'expected_from' =>  now()->addMinutes($currentTime),
                    'expected_to' =>  now()->addMinutes($currentTime + $time),
                    'expected_time' =>  $time,
                    'distance' =>  $item['distance'],
                    'rank' =>  $rank,
                    'order_id' =>  $item['order_id'],
                    'driver_id' =>  $this->activeCycle->driver_id,
                    'area_id' =>  $this->activeCycle->area_id,
                    'warehouse_id' =>  $this->activeCycle->warehouse_id,
                ];
                $this->activeCycle->orders()->create($data);

                $currentTime += $time;
                $rank++;
            }
        }
    }

    /**
     * 
     * @return void
     */
    private function saveExpectedBack()
    {
        $cachedData = Cache::get("cycle_{$this->activeCycle->id}_path");
        $lastOrder = $this->activeCycle->orders->last();
        $distanceBetweenLastOrderAndWarehouse = collect($cachedData)->where('order_id', $lastOrder->order_id)->where('source_type', 'warehouse')->first();

        // Save Expeexted Back
        $this->activeCycle->expected_back = Carbon::parse($this->activeCycle->expected_to)->addMinutes((int) ceil($distanceBetweenLastOrderAndWarehouse['time'] / 60));
        $this->activeCycle->save();
    }

    /**
     * 
     * @return void
     */
    private function saveFirstOrderActualTime()
    {
        try {
        $firstOrder = $this->activeCycle->orders->first();

        $firstOrder->actual_from = now();
        $firstOrder->save();
        }catch(\Exception $e){
            Log::info('WORKING CYCLE ERROR');
            Log::info($e);
        }
    }


    /**
     * 
     * @return array
     */
    private function getTotalPathTimeAndDistance()
    {
        $pathCollection = collect($this->roadPath);

        return [
            'time'      =>  (int) ceil($pathCollection->sum('time') / 60),
            'distance'  =>  (int) ceil($pathCollection->sum('distance'))
        ];
    }

    /**
     * @param array $warehouse
     * @param array $addresses
     * 
     * @return array
     */
    public function prepareOriginsAndDestinations(array $warehouse, array $addresses)
    {
        $warehouseLocation = [
            [
                'address_id'    => null,
                'lat' => $warehouse['location']['lat'],
                'long' => $warehouse['location']['long'],
                'order_id'  => null
            ]
        ];

        $locationsData = array_merge($warehouseLocation, $addresses);

        return [
            'origins' => $locationsData,
            'dsetinations' => $locationsData,
        ];
    }
}