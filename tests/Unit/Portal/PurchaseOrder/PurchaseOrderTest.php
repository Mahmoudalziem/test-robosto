<?php

namespace Tests\Unit\Portal\PurchaseOrder;

use Tests\TestCase;
use Illuminate\Support\Str;
use Webkul\User\Models\Admin;
use Illuminate\Support\Facades\Event;
use Webkul\Inventory\Models\InventoryArea;
use Webkul\Inventory\Models\InventoryProduct;
use Webkul\Inventory\Models\InventoryWarehouse;
use Webkul\Purchase\Models\PurchaseOrder;

class PurchaseOrderTest extends TestCase
{
    private $admin;

    public function setUp(): void
    {
        parent::setUp();
        $this->admin = Admin::find(1);
    }

    public function testCreatePurchaseOrderValidationWithouData()
    {
        $data = [];

        $this->actingAs($this->admin, 'admin')->postJson(
            route('admin.inventory.purchase-order.store'),
            $data
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'is_draft', 'supplier_id', 'area_id', 'warehouse_id', 'products'
            ], 'data.errors');
    }

    public function testCreatePurchaseOrderValidationWithSomeData()
    {
        $data = [
            'products'  =>  [
                ['id', 'qty', 'prod_date', 'exp_date']
            ]
        ];

        $this->actingAs($this->admin, 'admin')->postJson(
            route('admin.inventory.purchase-order.store'),
            $data
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'is_draft', 'supplier_id', 'area_id', 'warehouse_id', 'products.0.id', 'products.0.qty',
                'products.0.cost', 'products.0.prod_date', 'products.0.exp_date'
            ], 'data.errors');
    }

    public function testCreatePurchaseOrder()
    {
        $purchaseOrderCountBeforeCreate = PurchaseOrder::count();
        $productInventoryArea = InventoryArea::where(['product_id'  => 1, 'area_id' => 1])->first();
        $productInventoryWarehouse = InventoryWarehouse::where(['product_id'  => 1, 'area_id' => 1, 'warehouse_id'   =>  1])->first();
        $data = [
            'is_draft'   =>  0,
            'supplier_id'    =>  1,
            'area_id'    =>  1,
            'warehouse_id'    =>  1,
            'products' => [
                ['id'   =>  1, 'qty'    =>  12, 'cost'  =>  6, 'prod_date' =>  "2021-01-05", 'exp_date'    =>  "2021-01-15"],
                ['id'   =>  2, 'qty'    =>  8, 'cost'  =>  5, 'prod_date' =>  "2021-01-05", 'exp_date'    =>  "2021-01-15"],
            ]
        ];

        $response = $this->actingAs($this->admin, 'admin')->postJson(
            route('admin.inventory.purchase-order.store'),
            $data
        );

        $response->assertStatus(200)->assertJson(['status'    =>  200])->assertJsonStructure(['status', 'success', 'data']);

        // $newProduct = PurchaseOrder::first();
        $newPurchaseOrderID = $response['data']['id'];

        $this->assertDatabaseCount('purchase_orders', $purchaseOrderCountBeforeCreate + 1);
        $this->assertDatabaseHas('purchase_orders', ['id'  =>  $newPurchaseOrderID]);

        $this->assertDatabaseHas('purchase_order_products', [
            'purchase_order_id' => $newPurchaseOrderID,
            'product_id'    =>  1,
            'warehouse_id'    =>  1,
            'area_id'    =>  1,
            'qty'    =>  12,
            'cost'    =>  6,
            'amount'    =>  72,
            'prod_date' =>  "2021-01-05",
            'exp_date'    =>  "2021-01-15"
        ]);

        $this->assertDatabaseHas('inventory_products', [
            'product_id'    =>  1,
            'warehouse_id'    =>  1,
            'area_id'    =>  1,
            'qty'    =>  12,
            'cost'    =>  6,
            'amount'    =>  72,
            'prod_date' =>  "2021-01-05",
            'exp_date'    =>  "2021-01-15"
        ]);

        $this->assertDatabaseHas('inventory_areas', [
            'product_id'    =>  1,
            'area_id'    =>  1,
            'total_qty'    =>  $productInventoryArea->total_qty + 12
        ]);

        $this->assertDatabaseHas('inventory_warehouses', [
            'product_id'    =>  1,
            'area_id'    =>  1,
            'warehouse_id'    =>  1,
            'qty'    =>  $productInventoryWarehouse->qty + 12
        ]);
    }

    public function testUpdatePurchaseOrderValidationWithouData()
    {
        $purchaseOrderID = PurchaseOrder::latest()->first()->id;
        $data = [];

        $this->actingAs($this->admin, 'admin')->postJson(
            route('admin.inventory.purchase-order.update', $purchaseOrderID),
            $data
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'is_draft', 'supplier_id', 'area_id', 'warehouse_id', 'products'
            ], 'data.errors');
    }

    public function testUpdatePurchaseOrderValidationWithSomeData()
    {
        $purchaseOrderID = PurchaseOrder::latest()->first()->id;
        $data = [
            'products'  =>  [
                ['id', 'qty', 'prod_date', 'exp_date']
            ]
        ];

        $this->actingAs($this->admin, 'admin')->postJson(
            route('admin.inventory.purchase-order.update', $purchaseOrderID),
            $data
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'is_draft', 'supplier_id', 'area_id', 'warehouse_id', 'products.0.id', 'products.0.qty',
                'products.0.cost', 'products.0.prod_date', 'products.0.exp_date'
            ], 'data.errors');
    }


    public function testUpdatePurchaseOrder()
    {
        $purchaseOrder = PurchaseOrder::latest()->first();
        $purchaseOrderID = $purchaseOrder->id;
        $productInventoryArea = InventoryArea::where(['product_id'  => 3, 'area_id' => 1])->first();
        $productInventoryWarehouse = InventoryWarehouse::where(['product_id'  => 3, 'area_id' => 1, 'warehouse_id'   =>  1])->first();

        $data = [
            'is_draft'   =>  0,
            'supplier_id'    =>  1,
            'area_id'    =>  1,
            'warehouse_id'    =>  1,
            'products' => [
                ['id'   =>  3, 'qty'    =>  10, 'cost'  =>  6, 'prod_date' =>  "2021-01-06", 'exp_date'    =>  "2021-01-15"],
                ['id'   =>  4, 'qty'    =>  6, 'cost'  =>  5, 'prod_date' =>  "2021-01-05", 'exp_date'    =>  "2021-01-15"],
            ]
        ];

        $response =  $this->actingAs($this->admin, 'admin')->postJson(
            route('admin.inventory.purchase-order.update', $purchaseOrderID),
            $data
        );

        $response
            ->assertStatus(200)
            ->assertJson(['status'    =>  200])
            ->assertJsonStructure([
                'status', 'success', 'data'
            ]);

        $this->assertDatabaseHas('purchase_order_products', [
            'purchase_order_id' => $purchaseOrderID,
            'product_id'    =>  3,
            'warehouse_id'    =>  1,
            'area_id'    =>  1,
            'qty'    =>  10,
            'cost'    =>  6,
            'amount'    =>  60,
            'prod_date' =>  "2021-01-06",
            'exp_date'    =>  "2021-01-15"
        ]);

        $this->assertDatabaseHas('inventory_products', [
            'product_id'    =>  3,
            'warehouse_id'    =>  1,
            'area_id'    =>  1,
            'qty'    =>  10,
            'cost'    =>  6,
            'amount'    =>  60,
            'prod_date' =>  "2021-01-06",
            'exp_date'    =>  "2021-01-15"
        ]);

        $this->assertDatabaseHas('inventory_areas', [
            'product_id'    =>  3,
            'area_id'    =>  1,
            'total_qty'    =>  $productInventoryArea->total_qty + 10
        ]);

        $this->assertDatabaseHas('inventory_warehouses', [
            'product_id'    =>  3,
            'area_id'    =>  1,
            'warehouse_id'    =>  1,
            'qty'    =>  $productInventoryWarehouse->qty + 10
        ]);
    }

    public function testCreateDraftPurchaseOrder()
    {
        $purchaseOrderCountBeforeCreate = PurchaseOrder::count();
        $inventoryProducts = InventoryProduct::count();
        $productInventoryArea = InventoryArea::where(['product_id'  => 3, 'area_id' => 1])->first();
        $productInventoryWarehouse = InventoryWarehouse::where(['product_id'  => 3, 'area_id' => 1, 'warehouse_id'   =>  1])->first();
        $data = [
            'is_draft'   =>  1,
            'supplier_id'    =>  1,
            'area_id'    =>  1,
            'warehouse_id'    =>  1,
            'products' => [
                ['id'   =>  3, 'qty'    =>  12, 'cost'  =>  6, 'prod_date' =>  "2021-01-05", 'exp_date'    =>  "2021-01-15"],
                ['id'   =>  4, 'qty'    =>  8, 'cost'  =>  5, 'prod_date' =>  "2021-01-05", 'exp_date'    =>  "2021-01-15"],
            ]
        ];

        $response = $this->actingAs($this->admin, 'admin')->postJson(
            route('admin.inventory.purchase-order.store'),
            $data
        );

        $response->assertStatus(200)->assertJson(['status'    =>  200])->assertJsonStructure(['status', 'success', 'data']);

        // $newProduct = PurchaseOrder::first();
        $newPurchaseOrderID = $response['data']['id'];

        $this->assertDatabaseCount('purchase_orders', $purchaseOrderCountBeforeCreate + 1);
        $this->assertDatabaseHas('purchase_orders', ['id'  =>  $newPurchaseOrderID, 'is_draft'  =>  1]);

        $this->assertDatabaseHas('purchase_order_products', [
            'purchase_order_id' => $newPurchaseOrderID,
            'product_id'    =>  3,
            'warehouse_id'    =>  1,
            'area_id'    =>  1,
            'qty'    =>  12,
            'cost'    =>  6,
            'amount'    =>  72,
            'prod_date' =>  "2021-01-05",
            'exp_date'    =>  "2021-01-15"
        ]);

        $this->assertDatabaseCount('inventory_products', $inventoryProducts);

        $this->assertDatabaseHas('inventory_areas', [
            'product_id'    =>  3,
            'area_id'    =>  1,
            'total_qty'    =>  $productInventoryArea->total_qty
        ]);

        $this->assertDatabaseHas('inventory_warehouses', [
            'product_id'    =>  3,
            'area_id'    =>  1,
            'warehouse_id'    =>  1,
            'qty'    =>  $productInventoryWarehouse->qty
        ]);
    }

    public function testUpdatePurchaseOrderToIssued()
    {
        $purchaseOrderID = PurchaseOrder::latest()->first()->id;
        $productInventoryArea = InventoryArea::where(['product_id'  => 3, 'area_id' => 1])->first();
        $productInventoryWarehouse = InventoryWarehouse::where(['product_id'  => 3, 'area_id' => 1, 'warehouse_id'   =>  1])->first();

        $this->actingAs($this->admin, 'admin')->postJson(
            route('admin.inventory.purchase-order.update-to-issued', $purchaseOrderID)
        )
            ->assertStatus(200)
            ->assertJson(['status'    =>  200])
            ->assertJsonStructure([
                'status', 'success', 'data'
            ]);

        $this->assertDatabaseHas('purchase_orders', ['id'  =>  $purchaseOrderID, 'is_draft'  =>  0]);

        $this->assertDatabaseHas('purchase_order_products', [
            'purchase_order_id' => $purchaseOrderID,
            'product_id'    =>  3,
            'warehouse_id'    =>  1,
            'area_id'    =>  1,
            'qty'    =>  12,
            'cost'    =>  6,
            'amount'    =>  72,
            'prod_date' =>  "2021-01-05",
            'exp_date'    =>  "2021-01-15"
        ]);

        $this->assertDatabaseHas('inventory_products', [
            'product_id'    =>  3,
            'warehouse_id'    =>  1,
            'area_id'    =>  1,
            'qty'    =>  12,
            'cost'    =>  6,
            'amount'    =>  72,
            'prod_date' =>  "2021-01-05",
            'exp_date'    =>  "2021-01-15"
        ]);

        $this->assertDatabaseHas('inventory_areas', [
            'product_id'    =>  3,
            'area_id'    =>  1,
            'total_qty'    =>  $productInventoryArea->total_qty + 12
        ]);

        $this->assertDatabaseHas('inventory_warehouses', [
            'product_id'    =>  3,
            'area_id'    =>  1,
            'warehouse_id'    =>  1,
            'qty'    =>  $productInventoryWarehouse->qty + 12
        ]);
    }

    public function testCreateDraftPurchaseOrderWithMoneyDiscount()
    {
        $purchaseOrderCountBeforeCreate = PurchaseOrder::count();
        $data = [
            'is_draft'   =>  1,
            'supplier_id'    =>  1,
            'area_id'    =>  1,
            'warehouse_id'    =>  1,
            'discount_type' =>  'egp',
            'discount'      =>  40,
            'products' => [
                ['id'   =>  1, 'qty'    =>  5, 'cost'  =>  8, 'prod_date' =>  "2021-01-05", 'exp_date'    =>  "2021-01-15"],
                ['id'   =>  2, 'qty'    =>  4, 'cost'  =>  6, 'prod_date' =>  "2021-01-05", 'exp_date'    =>  "2021-01-15"],
            ]
        ];

        $response = $this->actingAs($this->admin, 'admin')->postJson(
            route('admin.inventory.purchase-order.store'),
            $data
        );

        $response->assertStatus(200)->assertJson(['status' => 200])->assertJsonStructure(['status', 'success', 'data']);

        // $newProduct = PurchaseOrder::first();
        $newPurchaseOrderID = $response['data']['id'];

        $this->assertDatabaseCount('purchase_orders', $purchaseOrderCountBeforeCreate + 1);
        $this->assertDatabaseHas('purchase_orders', ['id'  =>  $newPurchaseOrderID, 'discount_type'  =>  'egp', 'discount' => 40]);

        $this->assertDatabaseHas('purchase_order_products', [
            'purchase_order_id' => $newPurchaseOrderID,
            'product_id'    =>  1,
            'warehouse_id'    =>  1,
            'area_id'    =>  1,
            'qty'    =>  5,
            'cost_before_discount'    =>  8,
            'cost'    =>  4,
            'amount_before_discount'    =>  40,
            'amount'    =>  20,
            'prod_date' =>  "2021-01-05",
            'exp_date'    =>  "2021-01-15"
        ]);
    }

    public function testCreateDraftPurchaseOrderWithPercentageDiscount()
    {
        $purchaseOrderCountBeforeCreate = PurchaseOrder::count();
        $data = [
            'is_draft'   =>  1,
            'supplier_id'    =>  1,
            'area_id'    =>  1,
            'warehouse_id'    =>  1,
            'discount_type' =>  'per',
            'discount'      =>  15, // 15%
            'products' => [
                ['id'   =>  1, 'qty'    =>  5, 'cost'  =>  8, 'prod_date' =>  "2021-01-05", 'exp_date'    =>  "2021-01-15"],
                ['id'   =>  2, 'qty'    =>  4, 'cost'  =>  6, 'prod_date' =>  "2021-01-05", 'exp_date'    =>  "2021-01-15"],
            ]
        ];

        $response = $this->actingAs($this->admin, 'admin')->postJson(
            route('admin.inventory.purchase-order.store'),
            $data
        );

        $response->assertStatus(200)->assertJson(['status' => 200])->assertJsonStructure(['status', 'success', 'data']);

        // $newProduct = PurchaseOrder::first();
        $newPurchaseOrderID = $response['data']['id'];

        $this->assertDatabaseCount('purchase_orders', $purchaseOrderCountBeforeCreate + 1);
        $this->assertDatabaseHas('purchase_orders', ['id'  =>  $newPurchaseOrderID, 'discount_type'  =>  'per', 'discount'  =>  15]);

        $this->assertDatabaseHas('purchase_order_products', [
            'purchase_order_id' => $newPurchaseOrderID,
            'product_id'    =>  1,
            'warehouse_id'    =>  1,
            'area_id'    =>  1,
            'qty'    =>  5,
            'cost_before_discount'    =>  8,
            'cost'    =>  6.8,
            'amount_before_discount'    =>  40,
            'amount'    =>  34,
            'prod_date' =>  "2021-01-05",
            'exp_date'    =>  "2021-01-15"
        ]);
    }

    public function testGettingPurchaseOrders()
    {
        $this->actingAs($this->admin, 'admin')->getJson(route('admin.inventory.purchase-order.index'))
            ->assertStatus(200)
            ->assertJson(['success' =>  true])
            ->assertJsonStructure(
                [
                    'status', 'success', 'message',
                    'data'  =>  [
                        0   =>  [
                            'id',
                            'date',
                            'purchase_order_no',
                            'invoice_no',
                            'supplier',
                            'status',
                            'amount',
                            'warehouse',
                        ]
                    ]
                ]
            );
    }

    public function testShowPurchaseOrder()
    {
        $product = PurchaseOrder::first();

        $this->actingAs($this->admin, 'admin')->getJson(
            route('admin.inventory.purchase-order.show', $product)
        )
            ->assertStatus(200)
            ->assertJson(['success' =>  true])
            ->assertJsonStructure([
                'status',
                'success',
                'data'  =>  [
                    "id",
                    "date",
                    "purchase_order_no",
                    "invoice_no",
                    "supplier",
                    "warehouse",
                    "is_draft",
                    "total_cost",
                    "sub_total_cost",
                    "discount_type",
                    "discount",
                    "products"
                ]
            ]);
    }
    public function testgetSupplierBySku()
    {
        $inventoryProduct = InventoryProduct::first();

        $this->actingAs($this->admin, 'admin')->getJson(
            route('admin.app-management.products.get-supplier-by-sku', $inventoryProduct->sku)
        )
            ->assertStatus(200)
            ->assertJson(['success' =>  true])
            ->assertJsonStructure([
                'status',
                'success',
                'data'  =>  [
                    'id',
                    'name',
 
                ]
            ]);
    } 
    public function testProductsSearch()
    {
        $this->actingAs($this->admin, 'admin')->getJson(route('admin.inventory.purchase-order.products-search', ['q'  =>  'a']))
            ->assertStatus(200)
            ->assertJson(['success' =>  true])
            ->assertJsonStructure(
                [
                    'status', 'success', 'message',
                    'data'  =>  [
                        0   =>  [
                            'id',
                            'name',
                            'image',
                            'image_url',
                            'total_in_stock'
                        ]
                    ]
                ]
            );
    }


    public function testWarehousesSearch()
    {
        $this->actingAs($this->admin, 'admin')->getJson(route('admin.inventory.purchase-order.warehouses-search', ['q'  =>  'a']))
            ->assertStatus(200)
            ->assertJson(['success' =>  true])
            ->assertJsonStructure(
                [
                    'status', 'success', 'message',
                    'data'  =>  [
                        0   =>  [
                            'id',
                            'name',
                            'area_id',
                        ]
                    ]
                ]
            );
    }
}
