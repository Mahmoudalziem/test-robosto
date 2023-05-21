<?php

namespace Webkul\Driver\Repositories;

use Carbon\Carbon;
use Webkul\Sales\Models\Order;
use Webkul\Driver\Models\Driver;
use Illuminate\Support\Facades\Log;
use Webkul\Core\Eloquent\Repository;
use Illuminate\Support\Facades\Cache;
use Webkul\Sales\Models\OrderAddress;
use Webkul\Driver\Models\WorkingCycle;
use Illuminate\Container\Container as App;
use Illuminate\Database\Eloquent\Collection;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Driver\Services\DistanceResponseHandling;
use Webkul\Core\Services\LocationService\Distance\DistanceService;
use Webkul\Driver\Services\DriverStartDelivery;
use Webkul\Driver\Services\NewOrderAssigned;
use Webkul\Driver\Services\OrderCancelled;
use Webkul\Driver\Services\OrderDelivered;

class WorkingCycleRepository extends Repository
{
    protected $driverRepository;
    protected $orderRepository;

    /**
     * Create a new repository instance.
     *
     * @param DriverRepository $driverRepository
     * @param OrderRepository $orderRepository
     * @param App $app
     */
    public function __construct(
        DriverRepository $driverRepository,
        OrderRepository $orderRepository,
        App $app
    ) {
        $this->driverRepository = $driverRepository;
        $this->orderRepository = $orderRepository;
        parent::__construct($app);
    }

    /**
     * @param int $orderId
     * 
     * @return bool
     */
    public function newOrderAssigned(int $orderId)
    {
        $order = $this->orderRepository->find($orderId);
        $driver = $this->driverRepository->find($order->driver_id);

        $activeCycle = $this->getDriverActiveCycle($driver);
        $newOrderAssignedService = new NewOrderAssigned($this);
        
        if ($activeCycle) {
            Log::info("The Driver " . $driver->id . " Has Active Cycle");
            $newOrderAssignedService->updateActiveCycle($activeCycle);
            return true;
        }

        Log::info("The Driver " . $driver->id . " Has NOO Active Cycle");
        $newOrderAssignedService->createNewCycle($order, $driver);

        return true;
    }
    
    /**
     * @param int $driverId
     * 
     * @return bool
     */
    public function driverStartDelivery(int $driverId)
    {
        $driver = $this->driverRepository->find($driverId);

        $activeOrders = $this->orderRepository->findWhere(['status' => Order::STATUS_ON_THE_WAY])->where('driver_id', $driver->id);

        $warehouse = $this->getWarehouseData($driver);
    
        $addresses = $this->getAddressesData($activeOrders);

        $activeCycle = $this->getDriverActiveCycle($driver);

        Log::info("SD: Active Cycle " . $activeCycle->id);

        $startDeliveryService = new DriverStartDelivery($this);
        $startDeliveryService->calculateAndSaveCycle($activeCycle, $warehouse, $addresses);
        
        return true;
    }

    /**
     * @param int $orderId
     * 
     * @return bool
     */
    public function orderDelivered(int $orderId)
    {
        $order = $this->orderRepository->find($orderId);
        $driver = $this->driverRepository->find($order->driver_id);
        if(!$driver){
            Log::info("No Driver For Order " . $order->id);
            return true;
        }
        $activeCycle = $this->getDriverActiveCycle($driver);

        if (!$activeCycle) return false;

        Log::info("OD: Active Cycle " . $activeCycle->id);

        $orderDeliveredService = new OrderDelivered($activeCycle);

        Log::info("The Driver " . $driver->id . " Has been Delivered The Order " . $order->id);
        $orderDeliveredService->orderDelivered($order, $driver);

        return true;
    }
    
    
    /**
     * @param int $orderId
     * 
     * @return bool
     */
    public function orderCancelled(int $orderId)
    {
        $order = $this->orderRepository->find($orderId);
        
        (new OrderCancelled())->orderCancelled($order);

        return true;
    }

    /**
     * @param Driver $driver
     * 
     * @return bool|WorkingCycle
     */
    private function getDriverActiveCycle(Driver $driver)
    {
        return $this->findOneWhere(['driver_id' => $driver->id, 'status'   =>  WorkingCycle::ACTIVE_STATUS]);
    }
    
    
    /**
     * @param Driver $driver
     * 
     * @return array
     */
    private function getWarehouseData(Driver $driver)
    {
        $warehouse = $driver->warehouse;
        $warehouseLocation = ['lat' => $warehouse->latitude, 'long' => $warehouse->longitude];

        return [
            'data' => $warehouse,
            'location' => $warehouseLocation,
        ];
    }
    
    /**
     * @param Collection $activeOrders
     * 
     * @return array
     */
    private function getAddressesData(Collection $activeOrders)
    {
        $ordersReady = $activeOrders->pluck('id')->toArray();

        return OrderAddress::whereIn('order_id', $ordersReady)->get(['id As address_id', 'latitude AS lat', 'longitude AS long', 'order_id'])->toArray();
    }

    /**
     * Specify Model class name
     *
     * @return mixed
     */
    function model()
    {
        return 'Webkul\Driver\Contracts\WorkingCycle';
    }
}