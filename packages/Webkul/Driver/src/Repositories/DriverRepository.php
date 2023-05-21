<?php

namespace Webkul\Driver\Repositories;

use Carbon\Carbon;
use Webkul\Sales\Models\Order;
use App\Jobs\DriverBreakToIdle;
use App\Jobs\DriverOnlineStatus;
use Webkul\Driver\Models\Driver;
use Webkul\Core\Services\Measure;
use Webkul\Sales\Models\OrderItem;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Webkul\Core\Eloquent\Repository;
use Webkul\Core\Services\FileUpload;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;

use Illuminate\Support\Facades\Redis;
use Webkul\Sales\Models\OrderAddress;
use Webkul\Sales\Models\OrderLogsActual;
use Webkul\Driver\Models\DriverStatusLog;
use Illuminate\Container\Container as App;
use App\Jobs\DriverEmergencyStatusWithOrder;
use Illuminate\Database\Eloquent\Collection;
use Webkul\Motor\Repositories\MotorRepository;
use Webkul\Sales\Repositories\OrderRepository;
use Prettus\Repository\Exceptions\RepositoryException;
use Webkul\Sales\Repositories\Traits\OrderNotifications;
use LaravelFCM\Message\Exceptions\InvalidOptionsException;

class DriverRepository extends Repository
{

    use OrderNotifications;

    protected $motorRepository;
    protected $orderRepository;

    /**
     * Create a new repository instance.
     *
     * @param MotorRepository $motorRepository
     * @param OrderRepository $orderRepository
     * @param App $app
     */
    public function __construct(
        MotorRepository $motorRepository,
        OrderRepository $orderRepository,
        App $app
    )
    {
        $this->motorRepository = $motorRepository;
        $this->orderRepository = $orderRepository;
        parent::__construct($app);
    }


    /**
     * Specify Model class name
     *
     * @return mixed
     */
    function model()
    {
        return 'Webkul\Driver\Contracts\Driver';
    }


    // accept driver id , period , date_status_log,status
    public function setStatusLog($data, $driver)
    {

        Event::dispatch('driver.before.setStatusLog', $driver);
        $type = $data['type'];
        $statusLog['availability'] = $data['type'];
        $statusLog['status_log_date'] = Carbon::now();

        // set log row in driver status log table
        $driver->statusLogs()->create($statusLog);

        $driver->update(['availability' => $type]);

        Event::dispatch('driver.after.setStatusLog', $driver);

        return $driver;
    }

    public function confirmAtWarehouse( $driver)
    {

        Event::dispatch('driver.before.confirmAtWarehouse', $driver);

        $this->setStatusIdle($driver);
        Event::dispatch('driver.after.confirmAtWarehouse', $driver);

        return $driver;
    }


    public function setStatusLogin($driver)
    {
        $statusLog['availability'] = Driver::AVAILABILITY_ONLINE;
        $statusLog['status_log_date'] = Carbon::now();
        // set log row in driver status log table
        $driver->statusLogs()->create($statusLog);
        return $driver;
    }

    public function setStatusIdle($driver)
    {
        $type =  Driver::AVAILABILITY_IDLE;
        $statusLog['availability'] = $type;
        $statusLog['status_log_date'] = Carbon::now();
        // set log row in driver status log table
        $d=$driver->statusLogs()->create($statusLog);

        $driver->update(['availability' => $type, 'can_receive_orders' => Driver::CAN_RECEIVE_ORDERS]);

        Log::channel('queue-test')->info(['driver_id'=>$driver->id]);

        return $driver;
    }

    public function requestBreak($data, $driver)
    {
        Log::info('Duration');
        Log::info($data['duration']);
        $data['type'] =  Driver::AVAILABILITY_BREAK;
        Event::dispatch('driver.before.requestBreak', $driver);
        $driver->breakLogs()->create(['duration' => $data['duration']]);
        DriverBreakToIdle::dispatch($driver)->delay(Carbon::now()->addMinutes($data['duration']));

        // Publish New Order Status
        $this->publishDriverToRedis($driver, Driver::AVAILABILITY_BREAK);

        $driver->can_receive_orders = Driver::CANNOT_RECEIVE_ORDERS;
        $driver->save();

        $this->setStatusLog($data,$driver);
        Event::dispatch('driver.after.requestBreak', $driver);

        return $driver;
    }

    /**
     * @param $data
     * @param $driver
     * @return mixed
     * @throws RepositoryException
     */
    public function driverRequestEmergency($data, $driver)
    {
        $type = Driver::AVAILABILITY_EMERGENCY;
        $statusLog['availability'] = $type;
        $statusLog['status_log_date'] = Carbon::now();

        // Update Driver to Emergency
        $driver->update(['availability' => $type]);

        // set log row in driver status log table
        $driver->statusLogs()->create($statusLog);

        // Store Emergency Log
        $driver->emergencyLogs()->create(['reason' => $data['reason'], 'order_id' => $data['order_id'] ?? null]);

        // if this driver has order
        if (isset($data['order_id'])) {
            $order = $this->orderRepository->find($data['order_id']);
            $is = [];
            foreach ($order->items as $item) {
                $is[] = $item->replicate()->fill([
                    'order_id' => $order->id
                ])->save();
            }

            $aggregateOrderAddress = $order->items;

            // Call Job to Dispatch another Driver to this Order
            DriverEmergencyStatusWithOrder::dispatch($order);
        }

        Event::dispatch('driver.emergency', $driver);

        return true;
    }

    public function ordersHistory($driver)
    {
        // get all order history today
        $today = Carbon::today()->toDateString();
        return $driver->orders()->orderBy('created_at', 'desc')->whereIn('status', [Order::STATUS_DELIVERED, Order::STATUS_RETURNED, Order::STATUS_CANCELLED])->whereDate('created_at', $today)->get();
    }

    /**
     * @param array $data
     * @return bool
     * @throws RepositoryException
     */
    public function customerReturnedOrder(array $data)
    {
        // Get Order
        $order = $this->orderRepository->find($data['order_id']);
        // check order in at_place Status
        if ($order->status != Order::STATUS_AT_PLACE) {
            return false;
        }

        // Update Order To Cancelled
        $order->status = Order::STATUS_CANCELLED;
        $order->cancelled_reason = isset($data['reason']) ? $data['reason'] : null;
        $order->save();

        Event::dispatch('order.actual_logs', [$order, OrderLogsActual::ORDER_CUSTOMER_RETURNED]);

        return true;
    }

    /**
     * @param array $data
     * @return Order | boolean
     * @throws RepositoryException
     * @throws InvalidOptionsException
     */
    public function customerUpdatedOrder(array $data)
    {
        // Get Order
        $order = $this->orderRepository->find($data['order_id']);
        // check order in at_place Status
        if ($order->status != Order::STATUS_AT_PLACE) {
            return false;
        }

        Event::dispatch('order.actual_logs', [$order, OrderLogsActual::ORDER_CUSTOMER_UPDATED]);

        return $this->orderRepository->calculateOrderAfterDriverUpdated($order, $data['items']);
    }

    /**
     * @param array $data
     * @return bool
     * @throws RepositoryException
     * @throws InvalidOptionsException
     */
    public function reachedToWarehouseWithReturnedOrder(array $data)
    {
        // Get Order
        $order = $this->orderRepository->find($data['order_id']);

        // check order in at_place Status
        $orderStatus = [Order::STATUS_CANCELLED, Order::STATUS_DELIVERED];

        if (!in_array($order->status, $orderStatus)) {
            return false;
        }

        $reason = Cache::get("order_{$order->id}_return")['return_reason'] ? Cache::get("order_{$order->id}_return")['return_reason'] : $order->cancelled_reason;

        // Update Order To Cancelled
        $detailsForCollector = [
            'title' => 'Order Returned',
            'body' => 'Order Returned',
            'details' => [
                'key' => 'order_returned',
                'order_id' => $order->id,
                'reason' => $reason ?? null
            ]
        ];

        $this->orderRepository->sendNotificationToCollector($order, $detailsForCollector);

        return true;
    }

    public function currentOrder($driver)
    {
        $currentOrders = $this->orderRepository
                                ->findWhereIn('status', [Order::STATUS_PREPARING, Order::STATUS_READY_TO_PICKUP, Order::STATUS_ON_THE_WAY, Order::STATUS_AT_PLACE])
                                ->where('driver_id', $driver->id);

        // Get At Place Orders
        if ($currentOrders->whereIn('status', [Order::STATUS_AT_PLACE])->isNotEmpty()) {
            return $currentOrders->whereIn('status', [Order::STATUS_AT_PLACE])->first();

        } elseif ($currentOrders->whereIn('status', [Order::STATUS_ON_THE_WAY])->isNotEmpty()) {

            $onTheWayOrders = $currentOrders->whereIn('status', [Order::STATUS_ON_THE_WAY]);

            return $this->reArrangeOnTheWayOrders($onTheWayOrders);


        } else {

            return $currentOrders->first();
        }

        return $currentOrders->first();
    }

    /**
     * @param Collection $onTheWayOrders
     *
     * @return mixed
     */
    private function reArrangeOnTheWayOrders(Collection $onTheWayOrders)
    {
        $driver = auth('driver')->user();
        $pointsArray = [];
        $ordersID = $onTheWayOrders->pluck('id')->toArray();
        $prioritizedOrder = Cache::get("driver_{$driver->id}_prioritize_order");
        if($prioritizedOrder){
            if(in_array($prioritizedOrder,$ordersID)){
                return $onTheWayOrders->where('id', $prioritizedOrder)->first();
            }
        }
        $addresses = OrderAddress::whereIn('order_id', $ordersID)->get();

        foreach ($addresses as $address) {
            $pointsArray[] = [
                $address->latitude, $address->longitude, $address->order_id
            ];
        }


        // Get Driver Warehouse
        $driverWarehouse = $driver->warehouse;

        // Get Driver Location from Cache (Redis)
        $driverData = Cache::get('driver_' . $driver->id);

        if ($driverData == null) {
            $driverData['lat'] = $driverWarehouse->latitude;
            $driverData['long'] = $driverWarehouse->longitude;
        }

        if (empty($driverData['lat']) || empty($driverData['long'])) {
            $driverData['lat'] = $driverWarehouse->latitude;
            $driverData['long'] = $driverWarehouse->longitude;
        }


        $ordersDistance = Measure::distanceMany($driverData['lat'], $driverData['long'], $pointsArray, 'K');

        // Sort the distances between each Driver and Customer based on time
        $array_column = array_column($ordersDistance, 'distance');
        array_multisort($array_column, SORT_ASC, $ordersDistance);

        return $onTheWayOrders->where('id', $ordersDistance[0]['data'])->first();
    }

    public function activeOrders($driver)
    {
        $currentOrders = $this->orderRepository->findWhereIn('status', [Order::STATUS_PREPARING, Order::STATUS_READY_TO_PICKUP, Order::STATUS_ON_THE_WAY, Order::STATUS_AT_PLACE])->where('driver_id', $driver->id);

        return $currentOrders;
    }

    public function currentOrderV2($driver)
    {
        $currentOrders = $this->orderRepository
            ->findWhereIn('status', [Order::STATUS_ON_THE_WAY, Order::STATUS_AT_PLACE])
            ->where('driver_id', $driver->id);

        // Get At Place Orders
        if ($currentOrders->whereIn('status', [Order::STATUS_AT_PLACE])->isNotEmpty()) {
            return $currentOrders->whereIn('status', [Order::STATUS_AT_PLACE])->first();
        } elseif ($currentOrders->whereIn('status', [Order::STATUS_ON_THE_WAY])->isNotEmpty()) {

            $onTheWayOrders = $currentOrders->whereIn('status', [Order::STATUS_ON_THE_WAY]);

            return $this->reArrangeOnTheWayOrders($onTheWayOrders);
        } else {

            return $currentOrders->first();
        }

        return $currentOrders->first();
    }

    public function activeOrdersV2($driver)
    {
        $currentOrders = $this->orderRepository->findWhereIn('status', [Order::STATUS_READY_TO_PICKUP, Order::STATUS_ON_THE_WAY])->where('driver_id', $driver->id);

        return $currentOrders;
    }

    /**
     * @param mixed $driver
     *
     * @return void
     */
    public function startDelivery($driver)
    {
        $driver->can_receive_orders = Driver::CANNOT_RECEIVE_ORDERS;
        $driver->availability = Driver::AVAILABILITY_DELIVERY;
        $driver->save();
    }

    public function motorLog($data, $driver)
    {

        $availability =  Driver::AVAILABILITY_ONLINE;
        // if this driver has Order, then the availability will be Delivery
        $currentOrder = $this->currentOrder($driver);
        if ($currentOrder) {
            $availability = Driver::AVAILABILITY_DELIVERY;
        }
        // Update Driver availability when take motor screenshot
        $driver->update(['availability' => $availability]);

        $data['image'] = $data['image'] && !empty($data['image']) ? FileUpload::saveImgBase64($data['image'], 'driver/' . $driver->id . '/motor_conditions/') : null;
        return $driver->motors()->attach($data['motor_id'], ['image' => $data['image'], 'status' => 1]);
    }

    /**
     * @param Driver $driver
     *
     * @return bool
     */
    public function assignNewOrdersToDriver(Driver $driver) : bool
    {
        Log::info(["Is Driver - {$driver->id} - Can Receive New Order ??" => $driver->can_receive_orders == Driver::CANNOT_RECEIVE_ORDERS ? "NO" : "YES"]);
        // First Check that the driver can recieve orders
        if ($driver->can_receive_orders == Driver::CANNOT_RECEIVE_ORDERS) {
            return false;
        }

        $areaOrders = Cache::get("area_{$driver->area_id}_orders");
        $index = 0;
        Log::info(["Orders In Cache" => $areaOrders]);
        if ($areaOrders && count($areaOrders)) {

            $firstPackage = $areaOrders[$index];
            // dd($areaOrders);
            if (count($firstPackage) <= $driver->max_delivery_orders) {
                $this->assignAllOrdersPackage($driver, $areaOrders, $firstPackage, $index);
                return true;
            }

            $this->assignSomeOrdersPackage($driver, $areaOrders, $index);
            return  true;

        }

        return true;
    }

    /**
     * @param Driver $driver
     * @param array $areaOrders
     * @param array $firstPackage
     * @param int $index
     *
     * @return void
     */
    private function assignAllOrdersPackage(Driver $driver, array $areaOrders, array $firstPackage, int $index)
    {
        // Get Orders and Assign them to the driver
        $this->assignedTheOrders($driver, $firstPackage);

        // Finally detach the orders from area
        unset($areaOrders[$index]);
        $areaOrders = array_values($areaOrders);
        Cache::put("area_{$driver->area_id}_orders", $areaOrders);
    }

    /**
     * @param Driver $driver
     * @param array $areaOrders
     * @param int $index
     *
     * @return void
     */
    private function assignSomeOrdersPackage(Driver $driver, array $areaOrders, $index)
    {
        $allowedOrders = array_slice($areaOrders[$index], 0, $driver->max_delivery_orders);

        // Get Orders and Assign them to the driver
        $this->assignedTheOrders($driver, $allowedOrders);

        // Finally detach the orders from area
        $diff = array_diff($areaOrders[$index], $allowedOrders);
        $areaOrders[$index] = array_values($diff);

        Cache::put("area_{$driver->area_id}_orders", $areaOrders);
    }

    /**
     * @param Driver $driver
     * @param array $ids
     *
     * @return void
     */
    private function assignedTheOrders(Driver $driver, array $ids)
    {
        $orders = Order::whereIn('id', $ids)->get();
        foreach ($orders as $order) {

            if ($order->status == Order::STATUS_READY_TO_PICKUP) {
                $order->driver_id = $driver->id;
                $order->save();

                $this->notifyTheDriver($order, $driver->id);

                Event::dispatch('driver.new-order-assigned', [$order->id]);
            }
        }

    }

    /**
     * @param Order $order
     * @param mixed $driverId
     *
     * @return void
     */
    private function notifyTheDriver(Order $order, $driverId)
    {
        $data = [
            'title' => "تم ارسال طلب جديد اليك",
            'body'  => "لقد تم اضافة طلب جديد في قائمة طلباتك الجاهزه للاستلام",
            'data'  => ['key' => 'new_order_assigned'],
        ];

        $this->sendNotificationToDriver($driverId, $order, $data);
    }


    /**
     * Publish New Order Status
     */
    private function publishDriverToRedis(Driver $driver, string $newStatus)
    {
        Redis::publish('driver.order.status.updated',
                json_encode([
                    'driver' => [
                        'id' => $driver->id,
                        'status'    => $newStatus
                    ]
                    ]
                )
        );
    }
}
