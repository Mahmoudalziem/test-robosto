<?php

namespace Tests\Unit\Portal\Order;

use Tests\TestCase;
use Illuminate\Support\Str;
use Webkul\User\Models\Admin;
use Webkul\Sales\Models\Order;
use Webkul\Product\Models\Product;
use Webkul\Customer\Models\Customer;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Webkul\Core\Models\Complaint;
use Webkul\Inventory\Models\InventoryArea;

class OrderTest extends TestCase
{
    private $admin;

    public function setUp(): void
    {
        parent::setUp();
        $this->admin = Admin::find(1);
    }

    public function testGettingOrders()
    {

        $this->actingAs($this->admin, 'admin')->getJson(route('admin.orders.order.index'))
            ->assertStatus(200)
            ->assertJson(['success' =>  true])
            ->assertJsonStructure(
                [
                    'status', 'success', 'message',
                    'data'  =>  [
                        0   =>  [
                            "id",
                            "increment_id",
                            "items_count",
                            "no_of_qty",
                            "status",
                            "status_name",
                            "order_flagged",
                            "flagged_at",
                            "price",
                            "payment_method",
                            "payment_method_title",
                            "area",
                            "warehouse",
                            "driver",
                            "address",
                            "customer_name",
                            "contact_customer",
                            "order_date",
                            "expected_on",
                            "delivered_at",
                        ]
                    ]
                ]
            );
    }


    public function testShowOrder()
    {
        $order = Order::first();

        $this->actingAs($this->admin, 'admin')->getJson(
            route('admin.orders.order.show', $order)
        )
            ->assertStatus(200)
            ->assertJson(['success' =>  true])
            ->assertJsonStructure([
                'status',
                'success',
                'data'  =>  [
                    "id",
                    "increment_id",
                    "status",
                    "status_name",
                    "source",
                    "order_flagged",
                    "flagged_at",
                    "area",
                    "warehouse",
                    "driver",
                    "collector",
                    "order_date",
                    "expected_on",
                    "delivered_at",
                    "payment_method",
                    "payment_method_title",
                    "customer_name",
                    "customer_mobile",
                    "customer_address",
                    "address_phone",
                    "order_comment",
                    "order_stars",
                    "order_note",
                    "order_sub_total",
                    "order_delivery_chargs",
                    "order_tax_amount",
                    "order_total",
                    "timeline",
                    "items",
                ]
            ]);
    }


    public function testCreateOrderValidation()
    {
        $data = [];

        $this->actingAs($this->admin, 'admin')->postJson(
            route('admin.orders.order.store'),
            $data
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'items', 'area_id', 'address_id', 'customer_id', 'payment_method_id'
            ], 'data.errors');
    }

    public function testCreateOrder()
    {
        Event::fake();
        Queue::fake();

        $ordersCountBeforeCreate = Order::count();
        $customer = Customer::first();
        $firstProduct = Product::find(1);
        $secondProduct = Product::find(2);
        $firstProductInventoryArea = InventoryArea::where([
            'product_id'  => $firstProduct->id, 'area_id' => 1
        ])->first();

        $secondProductInventoryArea = InventoryArea::where([
            'product_id'  => $secondProduct->id, 'area_id' => 1
        ])->first();

        $data = [
            'area_id'   =>  1,
            'customer_id' =>  $customer->id,
            'address_id'    =>  $customer->addresses->first()->id,
            'payment_method_id' =>  1,
            'items' => [
                [
                    'id'  =>  $firstProduct->id,
                    'qty'  =>  3,
                ],
                [
                    'id'  =>  $secondProduct->id,
                    'qty'  =>  5,
                ],
            ]
        ];


        $response = $this->actingAs($this->admin, 'admin')->postJson(
            route('admin.orders.order.store'),
            $data
        );

        $response
            ->assertStatus(200)
            ->assertJson(['status'    =>  200])
            ->assertJsonStructure([
                'status', 'success', 'message', 'data'
            ]);

        $newOrderID = Order::latest()->first()->id;

        $this->assertDatabaseCount('orders', $ordersCountBeforeCreate + 1);
        $this->assertDatabaseHas('orders', [
            'id'  =>  $newOrderID,
            'sub_total'   => ($firstProduct->price * 3) + ($secondProduct->price * 5),
        ]);
        $this->assertDatabaseHas('inventory_areas', [
            'area_id'   =>  1,
            'product_id'  =>  $firstProduct->id,
            'total_qty' => $firstProductInventoryArea->total_qty - 3,
        ]);

        $this->assertDatabaseHas('inventory_areas', [
            'area_id'   =>  1,
            'product_id'  =>  $secondProduct->id,
            'total_qty' => $secondProductInventoryArea->total_qty - 5,
        ]);
    }

    public function testOrderComplaint()
    {
        $order = Order::first();
        $complaints = Complaint::count();

        $this->actingAs($this->admin, 'admin')->postJson(
            route('admin.orders.order.complaint', [
                'order_id'  =>  $order->id,
                'customer_id'  =>  $order->customer_id,
                'text'  =>  'Test Complaint'
            ])
        )
            ->assertStatus(200)
            ->assertJson(['success' =>  true])
            ->assertJsonStructure([
                'status',
                'success',
                'data'
            ]);

        $this->assertDatabaseCount('complaints', $complaints + 1);
    }

    public function testCancelOrder()
    {
        Event::fake();
        Queue::fake();

        $order = Order::latest()->first();
        $firstProduct = $order->items->first();
        $secondProduct = $order->items->last();
        $firstProductInventoryArea = InventoryArea::where([
            'product_id'  => $firstProduct->product_id, 'area_id' => 1
        ])->first();

        $secondProductInventoryArea = InventoryArea::where([
            'product_id'  => $secondProduct->product_id, 'area_id' => 1
        ])->first();


        $response = $this->actingAs($this->admin, 'admin')->getJson(
            route('admin.orders.order.cancel', ['order_id'   =>  $order->id]),
        );

        $response
            ->assertStatus(200)
            ->assertJson(['status'    =>  200])
            ->assertJsonStructure([
                'status', 'success', 'data'
            ]);

        $this->assertDatabaseHas('orders', [ 'id'  =>  $order->id, 'status'   => Order::STATUS_CANCELLED ]);
        $this->assertDatabaseHas('inventory_areas', [
            'area_id'   =>  1,
            'product_id'  =>  $firstProduct->product_id,
            'total_qty' => $firstProductInventoryArea->total_qty + $firstProduct->qty_ordered,
        ]);

        $this->assertDatabaseHas('inventory_areas', [
            'area_id'   =>  1,
            'product_id'  =>  $secondProduct->product_id,
            'total_qty' => $secondProductInventoryArea->total_qty + $secondProduct->qty_ordered,
        ]);
    }
}
