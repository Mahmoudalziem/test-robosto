<?php

namespace Webkul\Sales\Repositories\OrderServices;

use Webkul\Area\Models\Area;
use Webkul\Driver\Models\Driver;
use Webkul\Core\Services\Measure;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Webkul\Sales\Models\OrderAddress;
use Illuminate\Database\Eloquent\Collection;
use Webkul\Sales\Models\Order as OrderModel;
use Webkul\Sales\Repositories\Traits\OrderNotifications;

class RoboDistanceService
{
    use OrderNotifications;
    /**
     * @var Collection
     */
    private $availableDrivers;

    /**
     * @var Collection
     */
    private $readyToPickupOrders;

    /**
     * @var Area
     */
    private $area;

    /**
     * @param OrderModel $order
     *
     * @return bool
     */
    public function collectorPreparedOrder(OrderModel $order)
    {
        // Get Online Drivers in whole the system ANd Ready to pickup orders
        $this->getActiveDriversAndReadyOrders($order);

        // 1- if there are online drivers
        if (count($this->availableDrivers) != 0) {
            Log::info("there are online drivers");
            $this->dispatchWhenSomeDriversAreAvailable($order);
            return true;
        }
        Log::info("No online drivers");
        // 2- If there are no online drivers
        $this->dispatchWhenNoDriversAreAvailable($order);
        return true;
    }

    /**
     * @param OrderModel $order
     *
     * @return void
     */
    private function getActiveDriversAndReadyOrders(OrderModel $order)
    {
        $this->area = Area::find($order->area_id);

        $this->availableDrivers = Driver::where('drivers.default_driver', '!=', Driver::DEFAULT_DRIVER)
            ->where('drivers.warehouse_id', $order->warehouse_id)
            ->where('can_receive_orders', Driver::CAN_RECEIVE_ORDERS)
            ->where('drivers.status', 1)
            ->where('drivers.is_online', 1)
            ->orderBy('last_order_date')
            ->get();
            // ->join('orders', 'orders.driver_id', '=', 'drivers.id')
            // ->where('orders.status', OrderModel::STATUS_DELIVERED)
            // ->orderBy('O_created_at', 'ASC')
            // ->select('drivers.*', 'orders.id AS O_id', 'orders.driver_id AS O_driver_id', 'orders.created_at AS O_created_at')
        
        // if (count($this->availableDrivers) != 0) {
        //     $this->availableDrivers = $this->availableDrivers->unique('id');
        // }

        $this->readyToPickupOrders = OrderModel::where('warehouse_id', $order->warehouse_id)
            ->where('status', OrderModel::STATUS_READY_TO_PICKUP)
            ->with('address')
            ->get();

        Log::info(["Online Drivers" => $this->availableDrivers->pluck('id')->toArray()]);
        Log::info(["Ready Orders" => $this->readyToPickupOrders->pluck('id')->toArray()]);
    }


    /**
     * @param OrderModel $order
     *
     * @return void
     */
    public function dispatchWhenSomeDriversAreAvailable(OrderModel $order)
    {
        // Else, Search for nearest order in specific distance
        $driversWithOrders = $this->prepareDriversWithOrders($order);
        Log::info(["Driver With Orders" => $driversWithOrders]);

        // Loop through drivers and find nearest driver and can recieve order
        $this->searchForOrdersDrivers($order, $driversWithOrders);
    }

    /**
     * @param OrderModel $order
     *
     * @return array
     */
    private function prepareDriversWithOrders(OrderModel $order)
    {
        $readyDrivers = [];
        $orderAddress = $order->address;

        // Fill Drivers Array
        foreach ($this->availableDrivers as $driver) {

            $driverOrders = $this->readyToPickupOrders->where('driver_id', $driver->id);
            // if the driver has no orders
            if (count($driverOrders) == 0) {
                $readyDrivers[$driver->id] = [];
                continue;
            }
            // if the driver reached to orders limit
            if (count($driverOrders) >= $driver->max_delivery_orders) {
                continue;
            }

            // ELse, Loop through the driver orders and get nearest orders
            $this->getNearestOrderToDriver($readyDrivers, $driverOrders, $orderAddress, $driver);
        }

        return $readyDrivers;
    }

    /**
     * @param array $readyDrivers
     * @param Collection $driverOrders
     * @param OrderAddress $orderAddress
     * @param Driver $driver
     *
     * @return array
     */
    private function getNearestOrderToDriver(array &$readyDrivers, Collection $driverOrders, OrderAddress $orderAddress, Driver $driver)
    {
        foreach ($driverOrders as $driverOrder) {

            $driverOrderrAddress = $driverOrder->address;
            $distance = Measure::abstractDistance($orderAddress->latitude, $orderAddress->longitude, $driverOrderrAddress->latitude, $driverOrderrAddress->longitude);

            if (isset($readyDrivers[$driver->id])) {
                if ($readyDrivers[$driver->id]['distance'] <= $distance) {
                    $readyDrivers[$driver->id]['orders_count'] += 1;
                } else {
                    $this->fillDriverData($readyDrivers, $driver->id, $driverOrder->id, $distance, $readyDrivers[$driver->id]['orders_count'] + 1);
                }
            } else {

                $this->fillDriverData($readyDrivers, $driver->id, $driverOrder->id, $distance, 1);
            }
        }
    }

    /**
     * @param mixed $readyDrivers
     * @param mixed $driverId
     * @param mixed $orderId
     * @param mixed $distance
     * @param mixed $count
     *
     * @return array
     */
    private function fillDriverData(&$readyDrivers, $driverId, $orderId, $distance, $count)
    {
        $readyDrivers[$driverId] = [
            'order_id' => $orderId,
            'distance' =>  $distance,
            'orders_count'  => $count
        ];
    }

    /**
     * @param OrderModel $order
     * @param array $driversWithOrders
     *
     * @return void
     */
    private function searchForOrdersDrivers(OrderModel $order, array $driversWithOrders)
    {
        $orderDriverAssigned = null;

        // loop through nearest orders
        $this->getOptimalDriver($orderDriverAssigned, $driversWithOrders);
        Log::info(["Order Driver Assigned: " => $orderDriverAssigned]);

        if ($orderDriverAssigned) {
            $order->driver_id = $orderDriverAssigned['driver_id'];
            $order->save();

            Event::dispatch('driver.new-order-assigned', [$order->id]);

            $this->notifyTheDriver($order, $orderDriverAssigned['driver_id']);

        } else {
            $this->dispatchWhenNoDriversAreAvailable($order);
        }
    }

    /**
     * @param mixed $orderDriverAssigned
     * @param array $driversWithOrders
     *
     * @return mixed
     */
    private function getOptimalDriver(&$orderDriverAssigned, array $driversWithOrders)
    {
        Log::info(["Start In Optimal Drive" => $orderDriverAssigned]);
        foreach ($driversWithOrders as $key => $value) {

            if (count($value) == 0) {
                if (!isset($orderDriverAssigned['driver_id'])) {
                    $orderDriverAssigned = ['driver_id' => $key];
                }
            } else {

                // Check the minimum distance between orders for the driver
                if ($value['distance'] <= $this->area->min_distance_between_orders) {

                    if (isset($orderDriverAssigned['distance'])) {
                        if ($value['distance'] < $orderDriverAssigned['distance']) {
                            $orderDriverAssigned = ['driver_id' => $key, 'distance' => $value['distance']];
                        }
                    } else {
                        $orderDriverAssigned = ['driver_id' => $key, 'distance' => $value['distance']];
                    }
                }
            }
        }
    }

    /**
     * @param OrderModel $order
     *
     * @return void|bool
     */
    public function dispatchWhenNoDriversAreAvailable(OrderModel $order)
    {
        Log::info("Start Cache Orders that no drivers ready");
        // Start Divide Area into small areas
        $areaOrders = Cache::get("area_{$order->area_id}_orders");
        Log::info(["Orders In Cache" => $areaOrders]);
        if ($areaOrders && count($areaOrders)) {
            $addresses = $this->getReadyOrdersAddresses($areaOrders);
            Log::info(["Addresses Count" => count($addresses)]);
            if ($addresses) {
                $nearestOrder = $this->getNearestOrderToThisOrder($order, $addresses);
                // dd($nearestOrder);
                Log::info(["Nerest Ordaers" => $nearestOrder]);
                if ($nearestOrder) {
                    $areaHasOrder = $this->getSmallAraeThatHaveNearestOrder($nearestOrder['data'], $areaOrders);
                    // dd($areaHasOrder);
                    Log::info(["Area Has Orders" => $areaHasOrder]);
                    if ($areaHasOrder || $areaHasOrder === 0) {
                        $this->appendNewOrderToSmallArea($order, $areaHasOrder, $areaOrders);

                        return true;
                    }
                }
                // Else, Create a new small area for this order
                $this->newOrderInArea($order, $areaOrders);
            }

            return true;
        }

        $this->newOrderInArea($order, $areaOrders);
    }

    /**
     * @param array $areaOrders
     *
     * @return array
     */
    private function getReadyOrdersAddresses(array $areaOrders)
    {
        $allOrdersInCache = [];
        foreach ($areaOrders as $arrOrder) {
            $allOrdersInCache = array_merge($allOrdersInCache, $arrOrder);
        }

        $ordersReady = $this->readyToPickupOrders->whereIn('id', $allOrdersInCache)->pluck('id')->toArray();
        Log::alert(["Get-Ready-Orders" => $ordersReady]);

        $ordersAddresses = OrderAddress::whereIn('order_id', $ordersReady)->get(['latitude', 'longitude', 'order_id'])->toArray();
        $ordersAddressesFormatted = [];
        if (count($ordersAddresses)) {
            foreach ($ordersAddresses as $addr) {
                array_push($ordersAddressesFormatted, array_values($addr));
            }
        }
        return $ordersAddressesFormatted;
    }

    /**
     * @param OrderModel $order
     * @param array $addresses
     *
     * @return array
     */
    private function getNearestOrderToThisOrder(OrderModel $order, array $addresses)
    {
        Log::info(["All Addresses" => $addresses]);

        $ordersDistance = Measure::distanceMany($order->address->latitude, $order->address->longitude, $addresses);
        Log::info(["All Distances" => $ordersDistance]);
        $array_column = array_column($ordersDistance, 'distance');
        Log::info(["Array Column" => $array_column]);
        array_multisort($array_column, SORT_ASC, $ordersDistance);

        Log::info(["Orders Distance" => $ordersDistance]);

        if ($ordersDistance && isset($ordersDistance[0]) && $ordersDistance[0]['distance'] <= $this->area->min_distance_between_orders) {
            return $ordersDistance[0];
        }

        return [];
    }

    /**
     * @param int $id
     * @param array $areaOrders
     *
     * @return int|null
     */
    private function getSmallAraeThatHaveNearestOrder(int $id, array $areaOrders)
    {
        foreach ($areaOrders as $key => $areaOrder) {
            if (in_array($id, $areaOrder)) {
                return $key;
            }
        }

        return null;
    }

    /**
     * @param OrderModel $order
     * @param int $areaHasOrder
     * @param array $areaOrders
     *
     * @return void
     */
    private function appendNewOrderToSmallArea(OrderModel $order, int $areaHasOrder, array $areaOrders)
    {
        Log::info("Start Append Orders to Cache");
        if (!in_array($order->id, $areaOrders[$areaHasOrder])) {
            Log::info("Process Append Orders to Cache");
            $areaOrders[$areaHasOrder][] = $order->id;
            Cache::put("area_{$order->area_id}_orders", $areaOrders);
        }
    }


    /**
     * @param OrderModel $order
     * @param mixed $areaOrders
     *
     * @return void
     */
    private function newOrderInArea(OrderModel $order, $areaOrders)
    {
        Log::info("New order In Cache");
        $areaOrders[] = [$order->id];
        Cache::put("area_{$order->area_id}_orders", $areaOrders);

        // if (count($areaOrders)) {

        // } else {
        //     Cache::put("area_{$order->area_id}_orders", [[$order->id]]);
        // }
    }


    /**
     * @param OrderModel $order
     * @param mixed $driverId
     *
     * @return void
     */
    private function notifyTheDriver(OrderModel $order, $driverId)
    {
        $data = [
            'title' => "تم ارسال طلب جديد اليك",
            'body'  => "لقد تم اضافة طلب جديد في قائمة طلباتك الجاهزه للاستلام",
            'data'  => ['key' => 'new_order_assigned'],
        ];

        $this->sendNotificationToDriver($driverId, $order, $data);
    }
}
