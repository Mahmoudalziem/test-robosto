<?php

namespace Webkul\Sales\Repositories;

use App\Jobs\CheckOrderItems;
use App\Jobs\CustomerAcceptedOrderChanges;
use App\Jobs\CustomerCancelledOrder;
use App\Jobs\DriverAcceptedNewOrder;
use App\Jobs\DriverRejectedNewOrder;
use App\Jobs\GetAndStoreDrivers;
use App\Jobs\OrderProcessing;
use App\Jobs\SendOrderToDriver;
use Carbon\Carbon;
use Exception;
use Illuminate\Container\Container as App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use LaravelFCM\Message\Exceptions\InvalidOptionsException;
use Webkul\Collector\Models\Collector;
use Webkul\Core\Eloquent\Repository;
use Webkul\Core\Models\Channel;
use Webkul\Core\Services\LocationService\Distance\DistanceService;
use Webkul\Core\Services\SendPushNotification;
use Webkul\Customer\Models\Customer;
use Webkul\Customer\Models\CustomerAddress;
use Webkul\Driver\Models\Driver;
use Webkul\Inventory\Models\InventoryArea;
use Webkul\Inventory\Models\InventoryProduct;
use Webkul\Inventory\Models\InventoryWarehouse;
use Webkul\Inventory\Models\Warehouse;
use Webkul\Product\Models\Product;
use Webkul\Sales\Contracts\Order;
use Webkul\Sales\Models\Order as OrderModel;
use Webkul\Sales\Models\OrderAddress;
use Webkul\Sales\Models\OrderDriverDispatch;
use Webkul\Sales\Models\OrderLogsActual;
use Webkul\Sales\Services\NewOrderFilters\CheckItemsAvailableInAreaWarehouses;

class AggregateOrderRepository extends Repository
{
    /**
     * Order object
     *
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * Create a new repository instance.
     *
     * @param OrderRepository $orderRepository
     * @param App $app
     */
    public function __construct(OrderRepository $orderRepository, App $app)
    {
        $this->orderRepository = $orderRepository;
        parent::__construct($app);
    }

    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return Order::class;
    }

    /**
     * @param OrderModel $order
     * @return bool
     * @throws Exception
     */
    public function placeAggregateOrder(OrderModel $order)
    {
        // First Create Order
        $aggregateOrder = $this->createNewAggregateOrder($order);

        // Save Same Address For Aggregate Order
        $this->createAggregateOrderAddress($order, $aggregateOrder);

        // Save Same Address For Aggregate Order
        $this->createAggregateOrderItems($order, $aggregateOrder);

        // Fire Events
        Event::dispatch('app.order.placed', $aggregateOrder);
        Event::dispatch('order.actual_logs', [$aggregateOrder, OrderLogsActual::ORDER_PLACED]);

        logOrderActionsInCache($aggregateOrder->id, 'order_placed');

        // if Old order was in Prepairing Status
        if ($order->status == OrderModel::STATUS_PREPARING) {
            $this->checkDriveresFromOldOrderWarehouse($order, $aggregateOrder);
        }

        // if Old order was in On the Way || At Place Status
        if ($order->status == OrderModel::STATUS_ON_THE_WAY || $order->status == OrderModel::STATUS_AT_PLACE) {
            $this->dispstchNewAggregateOrder($order, $aggregateOrder);
        }

        return true;
    }

    /**
     * @param OrderModel $order
     * @return OrderModel
     * @throws Exception
     */
    public function createNewAggregateOrder(OrderModel $order)
    {
        // First Create Order
        /** @var OrderModel $aggregateOrder */
        $aggregateOrder = $order->replicate()->fill([
                'status'    =>  OrderModel::STATUS_PENDING,
                'increment_id'  =>  $this->orderRepository->generateIncrementId(),
                'aggregator_id' => $order->id,
                'driver_id' =>  null,
                'warehouse_id' =>  $order->status == OrderModel::STATUS_PREPARING ? $order->warehouse_id : null,
                'collector_id' =>  $order->status == OrderModel::STATUS_PREPARING ? $order->collector_id : null,
        ])->save();

        return $aggregateOrder;
    }

    /**
     * @param OrderModel $order
     * @param OrderModel $aggregateOrder
     */
    private function createAggregateOrderAddress(OrderModel $order, OrderModel $aggregateOrder)
    {
        $order->address->replicate()->fill([
            'order_id'  =>  $aggregateOrder->id
        ])->save();
    }

    /**
     * @param OrderModel $order
     * @param OrderModel $aggregateOrder
     */
    private function createAggregateOrderItems(OrderModel $order, OrderModel $aggregateOrder)
    {
        foreach ($order->items as $item) {
            $item->replicate()->fill([
                'order_id'  =>  $aggregateOrder->id
            ])->save();
        }
    }

    /**
     * @param OrderModel $order
     * @param OrderModel $aggregateOrder
     * @return bool
     * @throws InvalidOptionsException
     */
    private function checkDriveresFromOldOrderWarehouse(OrderModel $order, OrderModel $aggregateOrder)
    {
        // Get Idle Drivers from the same warehouse for old drivers
        $idleDrivers = $this->orderRepository->getIdleDrivers($order, [$order->warehouse_id]);

        // if Exist
        if ($idleDrivers->isNotEmpty()) {
            $readyDriver = $idleDrivers->first();
            // Process new Driver
            $this->availableDriversFoundInSameWarehouse($order, $aggregateOrder, $readyDriver);
            return true;
        }

        $this->dispstchNewAggregateOrder($order, $aggregateOrder);
        return false;
    }

    /**
     * @param OrderModel $order
     * @param OrderModel $aggregateOrder
     * @param Driver $driver
     * @return bool
     * @throws InvalidOptionsException
     */
    private function availableDriversFoundInSameWarehouse(OrderModel $order, OrderModel $aggregateOrder, Driver $driver)
    {
            // Update Order
            $aggregateOrder->driver_id = $driver->id;
            $aggregateOrder->save();

            // Send Force Order to Driver
            $detailsForDriver = [
                'title' =>  'New Order',
                'body'  =>  'New order',
                'details'   =>  [
                    'key'   =>  'force_new_order',
                    'order_id'  =>  $aggregateOrder->id
                ]
            ];
            $this->orderRepository->sendNotificationToDriver($driver->id, $aggregateOrder, $detailsForDriver);

            // Send Notification to Collector with New Driver
            $detailsForCollector = [
                'title' =>  'New Driver',
                'body'  =>  'New Driver',
                'details'   =>  [
                    'key'   =>  'driver_changed',
                    'old_order_id'  =>  $order->id,
                    'new_order_id'  =>  $aggregateOrder->id,
                    'driver_name'   =>  $aggregateOrder->driver->name
                ]
            ];
            $this->orderRepository->sendNotificationToCollector($aggregateOrder, $detailsForCollector);
        return true;
    }

    /**
     * @param OrderModel $order
     * @param OrderModel $aggregateOrder
     * @param Driver $driver
     * @return bool
     * @throws InvalidOptionsException
     */
    private function dispstchNewAggregateOrder(OrderModel $order, OrderModel $aggregateOrder)
    {
        // Update Order
        $aggregateOrder->status = OrderModel::STATUS_PENDING;
        $aggregateOrder->driver_id = null;
        $aggregateOrder->warehouse_id = null;
        $aggregateOrder->collector_id = null;
        $aggregateOrder->save();

        // Send Message to Customer with New Order
        $detailsForCustomer = [
                'title' =>  'Order Replaced',
                'body'  =>  'Sorry for old Order',
                'details'   =>  [
                    'key'   =>  'order_replaced',
                    'order_id'  =>  $aggregateOrder->id,
                    'order_status'  =>  $aggregateOrder->status,
                ]
            ];
        $this->orderRepository->sendNotificationToCustomer($aggregateOrder, $detailsForCustomer);

        // Send Notification to Collector with New Driver
        $detailsForCollector = [
                'title' =>  'Order Cancelled',
                'body'  =>  'Order Cancelled',
                'details'   =>  [
                    'key'   =>  'driver_cancelled',
                    'old_order_id'  =>  $order->id,
                    'new_order_id'  =>  $aggregateOrder->id
                ]
            ];
        $this->orderRepository->sendNotificationToCollector($aggregateOrder, $detailsForCollector);

        // If No Idle Drivers Found in the same Warehouse, dispatch new Order
        OrderProcessing::dispatch($aggregateOrder);

        return true;
    }

}
