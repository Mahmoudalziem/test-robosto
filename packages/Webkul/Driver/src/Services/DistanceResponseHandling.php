<?php
namespace Webkul\Driver\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Webkul\Driver\Models\WorkingCycle;

class DistanceResponseHandling {

    public const WAREHOUSE_SOURCE = 'warehouse';
    public const ORDER_SOURCE = 'order';
    protected $sortedDists=[];
    protected $validIndeces=[];

    protected $activeCycle;

    public function __construct(WorkingCycle $activeCycle)
    {
        $this->activeCycle = $activeCycle;
    }

    public function handleResponse(array $response, array $locations)
    {
        $resultFromatted = $this->reformateResponse($response, $locations['origins']);
        $this->getRoadPath($resultFromatted, 0, $locations['origins']);
        
        Cache::add("cycle_{$this->activeCycle->id}_path", $resultFromatted);
        // dd($this->sortedDists);
        
        return $this->sortedDists;
    }

    /**
     * @param array $response
     * @param array $locations
     * 
     * @return array
     */
    private function reformateResponse(array $response, array $locations)
    {
        $result = [];
        $index = 0;
        foreach ($response['rows'] as $key => $row) {
            foreach ($row['elements'] as $k => $el) {

                if ($el['status'] == 'OK' && $key != $k ) {
                    // "key_{$index}"
                    $result[] = [
                        'source_type' => $key == 0 ? self::WAREHOUSE_SOURCE : self::ORDER_SOURCE,
                        'source_id' => $key,
                        'destination_id' => $k,
                        'distance' => $el['distance']['value'],
                        'time' => $el['duration']['value'],
                        'order_id' => $k == 0 ? null : $locations[$k]['order_id']
                    ];
                    $index++;
                }
            }
        }

        return $result;
    }

    /**
     * @param mixed $result
     * @param mixed $nextItem
     * 
     * @return array
     */
    private function getRoadPath($result, $sourceId, $locations)
    {
        if (count($locations) == count($this->sortedDists) + 1) {
            return true;
        }
        
        $resultCollection = collect($result);
        $min = 100000000;
        $nextObject = null;
        $currentObject = null;
        $resultCollection->where('source_id', $sourceId)->map(function ($item, $key) use (&$min, &$nextObject, &$currentObject) {            
            if ($min > $item['time'] && $key != $item['destination_id'] && !in_array($item['destination_id'], $this->validIndeces)  ) {
                $min = $item['time'];
                $currentObject = $item;
                $nextObject = $item['destination_id'];
            }
        });
        array_push($this->validIndeces, $sourceId);
        array_push($this->sortedDists, $currentObject);
        $this->getRoadPath($result, $nextObject, $locations);
    }
}