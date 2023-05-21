<?php
namespace Webkul\Sales\Repositories\Traits;

use Illuminate\Support\Facades\Cache;
use Webkul\Inventory\Models\Warehouse;
use Illuminate\Database\Eloquent\Collection;
use Webkul\Collector\Models\Collector;

/**
 * Send Notifications to Drivers, Collectors, Customers
 */
trait CollectorRoundRobin
{
    /**
     * @param int $warehouseId
     * 
     * @return Collection
     */
    public function getReadyCollector(int $warehouseId)
    {
        $warehouseCollectors = Collector::where('can_receive_orders', Collector::CAN_RECEIVE_ORDERS)->where('warehouse_id', $warehouseId)->where('availability','online')->get()->toArray();
        
        $currentCollectorIndex = Cache::get("warehouse_{$warehouseId}_current_collectors_index");
        
        if ($currentCollectorIndex == null) {            
            return $this->getFirstCollector($warehouseId, $warehouseCollectors);
        }
        
        if (!isset($warehouseCollectors[$currentCollectorIndex])) {
            return $this->getFirstCollector($warehouseId, $warehouseCollectors);
        }

        // Get Current Collector
        $targetCollectorIndex = $warehouseCollectors[$currentCollectorIndex];
        
        // Update Index
        $currentCollectorIndex++;

        if ($currentCollectorIndex >= count($warehouseCollectors)) {
            // Store Initalize Position
            Cache::put("warehouse_{$warehouseId}_current_collectors_index", 0);
        }

        // Store Incremented Index
        Cache::put("warehouse_{$warehouseId}_current_collectors_index", $currentCollectorIndex);

        return Collector::find($targetCollectorIndex['id']);

    }

    /**
     * @param int $warehouseId
     * @param array $warehouseCollectors
     * 
     * @return Collection
     */
    public function getFirstCollector(int $warehouseId, array $warehouseCollectors)
    {
        // Store Initalize Position
        Cache::put("warehouse_{$warehouseId}_current_collectors_index", 1);
        
        if (!count($warehouseCollectors)) {
            return Collector::where('warehouse_id', $warehouseId)->first();
        }

        return Collector::find($warehouseCollectors[0]['id']);
    }

}
