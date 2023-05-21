<?php

namespace Tests\Feature;

use Exception;
use Carbon\Carbon;
use Tests\TestCase;
use ReflectionClass;
use App\Jobs\CheckOrderItems;
use App\Jobs\OrderProcessing;
use App\Jobs\PayViaCreditCard;
use Webkul\Sales\Models\Order;
use App\Jobs\SendOrderToDriver;
use App\Jobs\GetAndStoreDrivers;
use Webkul\Driver\Models\Driver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use App\Jobs\DriverAcceptedNewOrder;
use App\Jobs\DriverRejectedNewOrder;
use Webkul\Customer\Models\Customer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Webkul\Collector\Models\Collector;
use Webkul\Inventory\Models\Warehouse;
use App\Jobs\AcceptOrderByDefaultDriver;
use Webkul\Sales\Models\OrderLogsActual;
use Webkul\Inventory\Models\InventoryArea;
use Webkul\Sales\Models\OrderLogsEstimated;
use Illuminate\Foundation\Testing\WithFaker;
use Webkul\Sales\Models\OrderDriverDispatch;
use Webkul\Sales\Repositories\OrderRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Webkul\Sales\Contracts\Order as OrderContract;
use Webkul\Sales\Repositories\OrderItemRepository;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Webkul\Sales\Services\NewOrderFilters\CheckItemsAvailableInAreaWarehouses;

class PlaceOrderTest extends TestCase
{
    private $repository;
    public $order;
    public $orderLog;
    protected static $migrationRun = false;

    public function setUp(): void
    {
        parent::setUp();
        $this->repository = resolve(OrderRepository::class);
        $this->order = Order::find(1);
        Cache::put('driver_1', ['lat'   =>  29.973480, 'long'   =>  31.282079]);
    }

    public function truncateOldOrders()
    {
        Schema::disableForeignKeyConstraints();
        DB::statement("TRUNCATE TABLE orders");
        DB::statement("TRUNCATE TABLE order_address");
        DB::statement("TRUNCATE TABLE order_comments");
        DB::statement("TRUNCATE TABLE order_driver_dispatches");
        DB::statement("TRUNCATE TABLE order_items");
        DB::statement("TRUNCATE TABLE order_item_skus");
        DB::statement("TRUNCATE TABLE order_logs_actual");
        DB::statement("TRUNCATE TABLE order_logs_estimated");
        DB::statement("TRUNCATE TABLE order_payment");
        DB::statement("TRUNCATE TABLE order_reviews");
        DB::statement("UPDATE drivers SET availability = 'idle' WHERE drivers.id = 1");
        DB::statement("UPDATE customers SET wallet = 0 WHERE 1");
        Schema::enableForeignKeyConstraints();
    }

    public function test_generate_increment_id()
    {
        $this->truncateOldOrders();
        $id = $this->repository->generateIncrementId();
        $this->assertEquals(1, $id);
    }

    public function test_create_order()
    {
        Queue::fake();
        $this->truncateOldOrders();
        $inventoryArea = DB::table('inventory_areas')->where('product_id', 1)->where('area_id', 1)->first();
        $data = [
            'customer_id'    =>  1,
            'channel_id'    =>  1,
            'address_id'    =>  1,
            'payment_method_id' =>  1,
            'items' =>  [
                ['id'   =>  1, 'qty'    =>  2],
                ['id'   =>  2, 'qty'    =>  1]
            ]
        ];

        $customer = Customer::find(1);
        $this->actingAs($customer, 'customer')
            ->postJson(route('orders.create'), $data, ['area'  =>  1])
            ->assertSessionHasNoErrors()
            ->assertStatus(200);

        $order = Order::first();
        $firstProductInOrder = $order->items->first();

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseHas('order_address', ['order_id'   =>  1]);
        $this->assertDatabaseCount('order_items', 2);

        // 2- Test Inventory Area Updated
        $this->assertDatabaseHas('inventory_areas', [
            'product_id'    =>  $firstProductInOrder->product_id,
            'area_id'    =>  $order->area_id,
            'total_qty'    =>  $inventoryArea->total_qty - $firstProductInOrder->qty_ordered,
        ]);
    }

    public function test_order_processing_job()
    {
        Event::fake();
        Queue::fake();

        $order = $this->order;
        $firstProductInOrder = $order->items->first();
        $inventoryArea = DB::table('inventory_areas')->where('product_id', $firstProductInOrder->product_id)->where('area_id', $order->area_id)->first();

        // Start Function
        $this->repository->orderProcessing($order);
        $orderLog = Cache::get('order_1_log');
        // 1- Test Cache
        $this->assertArrayHasKey('estimated_time', $orderLog);

        // 3- Test Estimated Preparing will stored correctly
        $preparingTime = $order->items_qty_shipped * config('robosto.QAUNTITY_PREPARING_TIME');
        $this->assertDatabaseHas('order_logs_estimated', [
            'order_id'  =>  $order->id,
            'log_type'  =>  'preparing_time',
            'log_time'  =>  $preparingTime,
        ]);

        // 4- Test Order Procesing Job dispatched
        Queue::assertPushed(function (CheckOrderItems $job) use ($order) {
            return $job->order->id == $order->id;
        });
    }

    public function test_items_found()
    {
        Event::fake();
        Queue::fake();

        $order = $this->order;
        $orderItems = $this->order->items;

        // Start Function
        $this->repository->checkOrderItems($order);
        
        // 1- Test items Found
        $checkItemsAvailableInWarehouses = new CheckItemsAvailableInAreaWarehouses($orderItems->toArray(), $order->area_id);
        $allWarehousesHaveItems = $checkItemsAvailableInWarehouses->getAllWarehousesHaveItems();
        $this->assertArrayHasKey('items_found', $allWarehousesHaveItems);
        if ($allWarehousesHaveItems['items_found']) {
            // 4- Test GetAndStoreDrivers Job dispatched
            Queue::assertPushed(function (AcceptOrderByDefaultDriver $job) use ($order) {
                return $job->order->id == $order->id;
            });
            
            Queue::assertPushed(function (PayViaCreditCard $job) use ($order) {
                return $job->order->id == $order->id;
            });
        }
    }

    // public function test_driver_dispatching_units()
    // {
    //     Event::fake();
    //     Queue::fake();

    //     $order = $this->order;
    //     $warehouses = [1, 2];

    //     // Test Drivers Found
    //     $drivers = $this->repository->getIdleDrivers($order, $warehouses);
    //     $this->assertNotEmpty($drivers);

    //     // Test Sorted Drivers By Shortest Time from Google Service
    //     $sortedDrivers = $this->repository->getSortedDriversByShortestTime($drivers, $order);
    //     $this->assertIsArray($sortedDrivers);
    //     $this->assertArrayHasKey('driver_id', $sortedDrivers[0]);
    //     $this->assertArrayHasKey('warehouse_id', $sortedDrivers[0]);
    //     $this->assertArrayHasKey('time', $sortedDrivers[0]);

    //     // Test insert Order Driver Dispatching
    //     $this->repository->insertOrderDriverDispatching($sortedDrivers, $order);
    //     $this->assertDatabaseHas('order_driver_dispatches', [
    //         'order_id'    =>  $order->id,
    //         'driver_id'    =>  $sortedDrivers[0]['driver_id']
    //     ]);

    //     // Test Whoele Function
    //     $this->repository->orderDriverDispatching($warehouses, $order);

    //     // Test SendOrderToDriver Job dispatched
    //     Queue::assertPushed(function (SendOrderToDriver $job) use ($order) {
    //         return $job->order->id == $order->id;
    //     });
    // }

    // public function test_driver_dispatching_function()
    // {
    //     Event::fake();
    //     Queue::fake();

    //     $order = $this->order;
    //     $warehouses = [1, 2];
    //     // Test Whoele Function
    //     $this->repository->orderDriverDispatching($warehouses, $order);

    //     // Test SendOrderToDriver Job dispatched
    //     Queue::assertPushed(function (SendOrderToDriver $job) use ($order) {
    //         return $job->order->id == $order->id;
    //     });
    // }

    // public function test_ready_driver_dispatch()
    // {
    //     Event::fake();
    //     Queue::fake();

    //     $order = $this->order;
    //     // Test Whoele Function
    //     $this->repository->dispatchReadyDriver($order);
    //     $readyDriver = Cache::get("order_{$order->id}_driver_notified");

    //     // Test Driver receive notification and pending
    //     $this->assertDatabaseHas('order_driver_dispatches', [
    //         'order_id'    =>  $order->id,
    //         'status'    =>  OrderDriverDispatch::STATUS_PENDING
    //     ]);

    //     // Assert Notification being send to driver
    //     $orderLog = Cache::get('order_1_log');
    //     $this->assertArrayHasKey('start_send_notification_to_driver', $orderLog);
    //     $this->assertArrayHasKey('notification_to_driver_now', $orderLog);

    //     // Test SendOrderToDriver Job dispatched after delay
    //     Queue::assertPushed(SendOrderToDriver::class, function ($job) {
    //         return !is_null($job->delay);
    //     });
    // }

    //    public function test_driver_reject_new_order_response()
    //    {
    //        Event::fake();
    //        Queue::fake();
    //
    //        $order = $this->order;
    //        $driver = Driver::find(1);
    //        $data = [
    //          'order_id'  =>  $order->id,
    //            'action'  =>  'cancel',
    //            'reason'  =>  'Traffic'
    //        ];
    //
    //        $this->actingAs($driver, 'driver')
    //            ->getJson(route('orders.new.driver.response', $data), ['Accept' => 'application/json'])
    //            ->assertStatus(200);
    //    }

    // public function test_driver_accept_new_order_response()
    // {
    //     Event::fake();
    //     Queue::fake();

    //     $order = $this->order;
    //     $driver = Driver::find(1);
    //     $warehouse = Warehouse::find(1);
    //     $firstProductInOrder = $this->order->items->first();
    //     $inventoryWarehouse = DB::table('inventory_warehouses')
    //         ->where('product_id', $firstProductInOrder->product_id)->where('area_id', $order->area_id)->where('warehouse_id', $warehouse->id)->first();

    //     $data = [
    //         'order_id'  =>  $order->id,
    //         'action'  =>  'confirm',
    //     ];

    //     $this->actingAs($driver, 'driver')
    //         ->getJson(route('orders.new.driver.response', $data), ['Accept' => 'application/json'])
    //         ->assertStatus(200);

    //     // Assert that the order belongs to driver and preparing
    //     $this->assertDatabaseHas('orders', [
    //         'id'    =>  $order->id,
    //         'driver_id'     => $driver->id,
    //         'status'    =>  Order::STATUS_PREPARING,
    //     ]);

    //     // Assert that the driver is in delivery now
    //     $this->assertDatabaseHas('drivers', [
    //         'id'     => $driver->id,
    //         'availability'    =>  Driver::AVAILABILITY_DELIVERY
    //     ]);

    //     // 2- Assert Inventory Warehouses Updated
    //     $this->assertDatabaseHas('inventory_warehouses', [
    //         'product_id'    =>  $firstProductInOrder->product_id,
    //         'area_id'    =>  $order->area_id,
    //         'warehouse_id'    =>  $warehouse->id,
    //         'qty'    =>  $inventoryWarehouse->qty - $firstProductInOrder->qty_ordered,
    //     ]);

    //     // 2- Assert Inventory Products (SKU) Updated
    //     // TODO: Implement Test Inventory Products (SKU) Updated, since there are more sku for the same product and each sku has qty

    //     $orderLog = Cache::get('order_1_log');
    //     // Assert Notification being send to customer
    //     $this->assertArrayHasKey('start_send_notification_to_customer', $orderLog);
    //     $this->assertArrayHasKey('notification_to_customer_now', $orderLog);

    //     // Assert Notification being send to collector
    //     $this->assertArrayHasKey('start_send_notification_to_collector', $orderLog);
    //     $this->assertArrayHasKey('notification_to_collector_now', $orderLog);

    //     // 2- Assert Delivery time stored
    //     $this->assertDatabaseHas('order_logs_estimated', [
    //         'order_id'  =>  $order->id,
    //         'log_type'    =>  OrderLogsEstimated::DELIVERY_TIME
    //     ]);
    // }

    //    public function test_driver_reject_new_order_process()
    //    {
    //        Event::fake();
    //        Queue::fake();
    //
    //        $order = $this->order;
    //        $driver = Driver::find(1);
    //        $reason = 'Traffic Propblem';
    //        // Run Function
    //        $this->repository->driverRejectedNewOrder($order, $driver, $reason);
    //
    //        // Test Driver Reject New Order
    //        $this->assertDatabaseHas('order_driver_dispatches', [
    //            'order_id'    =>  $order->id,
    //            'driver_id'     => $driver->id,
    //            'status'    =>  OrderDriverDispatch::STATUS_CANCELED,
    //            'reason'    =>  $reason
    //        ]);
    //
    //        // Test SendOrderToDriver Job dispatched after delay
    //        Queue::assertPushed(SendOrderToDriver::class, function ($job) use ($order) {
    //            return $job->order->id === $order->id;
    //        });
    //    }

    public function test_collector_order_ready_to_pickup()
    {
        Event::fake();
        $order = $this->order;
        $collector = Collector::find(1);
        $data = ['order_id'  =>  $order->id,];

        $this->actingAs($collector, 'collector')
            ->getJson(route('collector.order-ready-to-pickup', $data), ['Accept' => 'application/json'])
            ->assertStatus(200);

        // Assert that the order belongs to driver and preparing
        $this->assertDatabaseHas('orders', [
            'id'    =>  $order->id,
            'status'    =>  Order::STATUS_READY_TO_PICKUP,
        ]);

        $orderLog = Cache::get('order_1_log');
        // Assert Notification being send to driver
        $this->assertArrayHasKey('start_send_notification_to_driver', $orderLog);
        $this->assertArrayHasKey('notification_to_driver_now', $orderLog);
    }

    public function test_driver_order_confirm_receiving_items()
    {
        Event::fake();
        $order = $this->order;
        $driver = Driver::find(1);
        $data = ['order_id'  =>  $order->id,];

        $this->actingAs($driver, 'driver')
            ->getJson(route('orders.driver.confirm-receiving-items', $data), ['Accept' => 'application/json'])
            ->assertStatus(200);

        // Assert that the order belongs to driver and preparing
        $this->assertDatabaseHas('orders', [
            'id'    =>  $order->id,
            'status'    =>  Order::STATUS_ON_THE_WAY,
        ]);

        $orderLog = Cache::get('order_1_log');
        // Assert Notification being send to customer
        $this->assertArrayHasKey('start_send_notification_to_customer', $orderLog);
        $this->assertArrayHasKey('notification_to_customer_now', $orderLog);
    }

    public function test_driver_order_at_place()
    {
        Event::fake();
        $order = $this->order;
        $driver = Driver::find(1);
        $data = ['order_id'  =>  $order->id,];

        $this->actingAs($driver, 'driver')
            ->getJson(route('orders.driver.at-place', $data), ['Accept' => 'application/json'])
            ->assertStatus(200);

        // Assert that the order belongs to driver and preparing
        $this->assertDatabaseHas('orders', [
            'id'    =>  $order->id,
            'status'    =>  Order::STATUS_AT_PLACE,
        ]);

        $orderLog = Cache::get('order_1_log');
        // Assert Notification being send to customer
        $this->assertArrayHasKey('start_send_notification_to_customer', $orderLog);
        $this->assertArrayHasKey('notification_to_customer_now', $orderLog);
    }

    public function test_driver_order_delivered()
    {
        Event::fake();
        $order = $this->order;
        $driver = Driver::find(1);
        $data = ['order_id'  =>  $order->id, 'amount_collected' =>  84];

        $this->actingAs($driver, 'driver')
            ->getJson(route('orders.driver.delivered', $data), ['Accept' => 'application/json'])
            ->assertStatus(200);

        // Assert that the order belongs to driver and preparing
        $this->assertDatabaseHas('orders', [
            'id'    =>  $order->id,
            'status'    =>  Order::STATUS_DELIVERED,
        ]);
    }

    public function test_collected_amount_is_over_order_price()
    {
        $order = $this->order;
        $amount_collected = 120;
        // Start Function
        $response = $this->repository->checkCollectedAmount($order, $amount_collected);

        // 1- Test Response
        $this->assertEquals('done', $response['status']);
    }

    public function test_collected_amount_is_equal_order_price()
    {
        $order = $this->order;
        $amount_collected = 84;
        // Start Function
        $response = $this->repository->checkCollectedAmount($order, $amount_collected);

        // 1- Test Response
        $this->assertEquals('done', $response['status']);
    }

    public function test_collected_amount_is_less_than_order_price_and_buffer()
    {
        $order = $this->order;
        $amount_collected = 20;
        // Start Function
        $response = $this->repository->checkCollectedAmount($order, $amount_collected);

        // 1- Test Response
        $this->assertEquals('not_allowed', $response['status']);
    }

    public function test_collected_amount_is_less_than_order_price_but_more_than_buffer()
    {
        $order = $this->order;
        $amount_collected = 80;
        // Start Function
        $response = $this->repository->checkCollectedAmount($order, $amount_collected);

        // 1- Test Response
        $this->assertEquals('done', $response['status']);
    }

    public function test_customer_rating_order()
    {
        $order = $this->order;
        $customer = Customer::first();
        $data = [
            'order_id'  =>  $order->id,
            'rating'    =>  4,
            'comment'    =>  'nice',
        ];

        $this->actingAs($customer, 'customer')->postJson(
            route('orders.customer.rating'),
            $data
        )
            ->assertStatus(200)
            ->assertJson(['status'    =>  200])
            ->assertJsonStructure([
                'status', 'success', 'data'
            ]);

        $this->assertDatabaseHas('order_comments', [
            'order_id'  =>  $order->id,
            'rating'    =>  4,
            'comment'    =>  'nice',
        ]);
    }


    // public function test_customer_cancel_order()
    // {
    //     Event::fake();
    //     Queue::fake();

    //     $order = $this->order;
    //     $customer = Customer::first();
    //     $data = ['order_id'  =>  $order->id];

    //     $firstProduct = $order->items->first();
    //     $secondProduct = $order->items->last();
    //     $firstProductInventoryArea = InventoryArea::where([
    //         'product_id'  => $firstProduct->product_id, 'area_id' => 1
    //     ])->first();

    //     $secondProductInventoryArea = InventoryArea::where([
    //         'product_id'  => $secondProduct->product_id, 'area_id' => 1
    //     ])->first();
        
    //     $response = $this->actingAs($customer, 'customer')->postJson(
    //         route('orders.customer.cancelled'), $data
    //     );

    //     $response
    //         ->assertStatus(200)
    //         ->assertJson(['status'    =>  200])
    //         ->assertJsonStructure([
    //             'status', 'success', 'data'
    //         ]);

    //     $this->assertDatabaseHas('orders', ['id'  =>  $order->id, 'status'   => Order::STATUS_CANCELLED]);
    //     $this->assertDatabaseHas('inventory_areas', [
    //         'area_id'   =>  1,
    //         'product_id'  =>  $firstProduct->product_id,
    //         'total_qty' => $firstProductInventoryArea->total_qty + $firstProduct->qty_ordered,
    //     ]);

    //     $this->assertDatabaseHas('inventory_areas', [
    //         'area_id'   =>  1,
    //         'product_id'  =>  $secondProduct->product_id,
    //         'total_qty' => $secondProductInventoryArea->total_qty + $secondProduct->qty_ordered,
    //     ]);
    // }
}
