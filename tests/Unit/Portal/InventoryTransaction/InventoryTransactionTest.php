<?php

namespace Tests\Unit\Portal\Transaction;

use Tests\TestCase;
use Illuminate\Support\Str;
use Webkul\User\Models\Admin;
use Illuminate\Support\Facades\Event;
use Webkul\Inventory\Models\InventoryArea;
use Webkul\Inventory\Models\InventoryProduct;
use Webkul\Inventory\Models\InventoryWarehouse;
use Webkul\Inventory\Models\InventoryTransaction;
use Webkul\Product\Models\Product;

class InventoryTransactionTest extends TestCase
{
    private $admin;

    public function setUp(): void
    {
        parent::setUp();
        $this->admin = Admin::find(1);
    }

    public function testCreateTransactionValidationWithouData()
    {
        $data = [];

        $this->actingAs($this->admin, 'admin')->postJson(
            route('admin.inventory.transactions.store'),
            $data
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'from_warehouse_id', 'to_warehouse_id', 'products'
            ], 'data.errors');
    }

    public function testCreateTransactionValidationWithSomeData()
    {
        $data = [
            'products' =>  [
                0   =>  [
                    'product_id'    =>  1,
                    'skus'  =>
                    [
                        [
                            'inventory_product_id'  =>  null,
                            'product_id' =>  null,
                            'qty'    =>  0,
                            'sku'    =>  null
                        ]
                    ]
                ]
            ]
        ];

        $this->actingAs($this->admin, 'admin')->postJson(
            route('admin.inventory.transactions.store'),
            $data
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'from_warehouse_id', 'to_warehouse_id', 'products.0.skus.0.sku', 'products.0.skus.0.qty'
            ], 'data.errors');
    }

    public function testCreateTransactionValidationWithLessQuantity()
    {
        $data = [
            'from_warehouse_id' =>  1,
            'to_warehouse_id' =>  2,
            'products' =>  [
                0   =>  [
                    'product_id'    =>  1,
                    'skus'  =>
                    [
                        [
                            'inventory_product_id'  =>  1,
                            'product_id' =>  1,
                            'qty'    =>  500,
                            'sku'    =>  'PR1'
                        ]
                    ]
                ]
            ]
        ];

        $this->actingAs($this->admin, 'admin')->postJson(
            route('admin.inventory.transactions.store'),
            $data
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'products.0.skus.0.qty'
            ], 'data.errors');
    }

    public function testCreateTransaction()
    {
        $purchaseOrderCountBeforeCreate = InventoryTransaction::count();
        $productInventoryArea = InventoryArea::where(['product_id'  => 1, 'area_id' => 1])->first();
        $productInventoryWarehouse = InventoryWarehouse::where(['product_id'  => 1, 'warehouse_id'   =>  1])->first();
        $productInventoryProduct = InventoryProduct::first();
        $data = [
            'from_warehouse_id' =>  1,
            'to_warehouse_id' =>  2,
            'products' =>  [
                0   =>  [
                    'product_id'    =>  1,
                    'skus'  =>
                    [
                        [
                            'inventory_product_id'  =>  $productInventoryProduct->id,
                            'product_id' =>  1,
                            'qty'    =>  5,
                            'sku'    =>  $productInventoryProduct->sku
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->actingAs($this->admin, 'admin')->postJson(
            route('admin.inventory.transactions.store'),
            $data
        );

        $response->assertStatus(200)->assertJson(['status'    =>  200])->assertJsonStructure(['status', 'success', 'data']);

        // $newProduct = InventoryTransaction::first();
        $newTransactionID = $response['data']['id'];

        $this->assertDatabaseCount('inventory_transactions', $purchaseOrderCountBeforeCreate + 1);
        $this->assertDatabaseHas('inventory_transactions', ['id'  =>  $newTransactionID, 'status'   =>  1]);

        $this->assertDatabaseHas('inventory_transaction_products', [
            'qty'    =>  5,
            'sku'    =>  $productInventoryProduct->sku,
            'inventory_transaction_id' => $newTransactionID,
            'product_id'    =>  1,
            'inventory_product_id' =>  $productInventoryProduct->id
        ]);

        $this->assertDatabaseHas('inventory_products', [
            'id'    =>  $productInventoryProduct->id,
            'qty'    =>  $productInventoryProduct->qty - 5
        ]);

        $this->assertDatabaseHas('inventory_warehouses', [
            'product_id'    =>  1,
            'warehouse_id'    =>  1,
            'qty'    =>  $productInventoryWarehouse->qty - 5
        ]);

        $this->assertDatabaseHas('inventory_areas', [
            'product_id'    =>  1,
            'area_id'    =>  1,
            'total_qty'    =>  $productInventoryArea->total_qty - 5
        ]);
    }

    public function testGettingTransactions()
    {
        $this->actingAs($this->admin, 'admin')->getJson(route('admin.inventory.transactions.index'))
            ->assertStatus(200)
            ->assertJson(['success' =>  true])
            ->assertJsonStructure(
                [
                    'status', 'success', 'message',
                    'data'  =>  [
                        0   =>  [
                            "id",
                            "source",
                            "destinasation",
                            "transaction_type",
                            "status",
                            "created_at",
                        ]
                    ]
                ]
            );
    }

    public function testTransactionProfile()
    {
        $transaction = InventoryTransaction::first();

        $this->actingAs($this->admin, 'admin')->getJson(
            route('admin.inventory.transactions.show', $transaction)
        )
            ->assertStatus(200)
            ->assertJson(['success' =>  true])
            ->assertJsonStructure([
                'status',
                'success',
                'data'  =>  [
                    'id',
                    'source',
                    'sourceArea',
                    'destinasation',
                    'destinasationArea',
                    'transaction_type_value',
                    'transaction_type',
                    'status_id',
                    'status',
                    'products',
                    'created_at',
                ]
            ]);
    }

    public function testTransactionUpdateStatusToTranseferred()
    {
        $transaction = InventoryTransaction::first();
        $inventoryTransactionsProduct = $transaction->transactionProducts->first();
        $productInventoryProduct = InventoryProduct::where('warehouse_id', $transaction->to_warehouse_id)->first();
        $productInventoryWarehouse = InventoryWarehouse::where(['product_id'  => 1, 'warehouse_id'   =>  2])->first();
        $productInventoryArea = InventoryArea::where(['product_id'  => 1, 'area_id' => 1])->first();

        $this->actingAs($this->admin, 'admin')->putJson(
            route('admin.inventory.transactions.update-status', [
                'InventoryTransaction'  => $transaction,
                'status'    =>  3
            ])
        )
            ->assertStatus(200)
            ->assertJson(['success' =>  true])
            ->assertJsonStructure([
                'status',
                'success',
                'data'  =>  [
                    'id',
                    'from_warehouse',
                    'from_warehouse_id',
                    'status',
                    'status_name',
                    'to_warehouse',
                    'to_warehouse_id',
                    'transaction_products',
                    'transaction_type',
                    'created_at',
                    'updated_at',
                ]
            ]);

        $this->assertDatabaseHas('inventory_products', [
            'sku'    =>  $inventoryTransactionsProduct->sku,
            'warehouse_id'  =>  2,
            'qty'    =>  $productInventoryProduct ? $productInventoryProduct->qty + 5 : 5
        ]);

        $this->assertDatabaseHas('inventory_warehouses', [
            'product_id'    =>  1,
            'warehouse_id'    =>  2,
            'qty'    =>  $productInventoryWarehouse ? $productInventoryWarehouse->qty + 5 : 5
        ]);

        $this->assertDatabaseHas('inventory_areas', [
            'product_id'    =>  1,
            'area_id'    =>  1,
            'total_qty'    =>  $productInventoryArea->total_qty + 5
        ]);
    }

    public function testTransactionUpdateStatusToCancelled()
    {
        $transaction = InventoryTransaction::first();
        $inventoryTransactionsProduct = $transaction->transactionProducts->first();
        $productInventoryProduct = InventoryProduct::where('warehouse_id', $transaction->from_warehouse_id)->first();
        $productInventoryWarehouse = InventoryWarehouse::where(['product_id'  => 1, 'warehouse_id'   =>  1])->first();
        $productInventoryArea = InventoryArea::where(['product_id'  => 1, 'area_id' => 1])->first();

        $this->actingAs($this->admin, 'admin')->putJson(
            route('admin.inventory.transactions.update-status', [
                'InventoryTransaction'  => $transaction,
                'status'    =>  0
            ])
        )
            ->assertStatus(200)
            ->assertJson(['success' =>  true])
            ->assertJsonStructure([
                'status',
                'success',
                'data'  =>  [
                    'id',
                    'from_warehouse',
                    'from_warehouse_id',
                    'status',
                    'status_name',
                    'to_warehouse_id',
                    'transaction_products',
                    'transaction_type',
                    'created_at',
                    'updated_at',
                ]
            ]);

        $this->assertDatabaseHas('inventory_products', [
            'sku'    =>  $inventoryTransactionsProduct->sku,
            'warehouse_id'  =>  1,
            'qty'    =>  $productInventoryProduct->qty + 5
        ]);

        $this->assertDatabaseHas('inventory_warehouses', [
            'product_id'    =>  1,
            'warehouse_id'    =>  1,
            'qty'    =>  $productInventoryWarehouse->qty + 5
        ]);

        $this->assertDatabaseHas('inventory_areas', [
            'product_id'    =>  1,
            'area_id'    =>  1,
            'total_qty'    =>  $productInventoryArea->total_qty + 5
        ]);
    }

    public function testProductsSearch()
    {
        $this->actingAs($this->admin, 'admin')->getJson(
            route(
                'admin.inventory.transactions.products-search',
                [
                    'key'  =>  'a',
                    'from_warehouse_id'  =>  1,
                    'to_warehouse_id'  =>  2,
                ]
            )
        )
            ->assertStatus(200)
            ->assertJson(['success' =>  true])
            ->assertJsonStructure(
                [
                    'status', 'success',
                    'data'  =>  [
                        0   =>  [
                            "id",
                            "image_url",
                            "name"
                        ]
                    ]
                ]
            );
    }


    public function testSelectProduct()
    {
        $product = Product::first();
        $this->actingAs($this->admin, 'admin')->getJson(
            route(
                'admin.inventory.transactions.select-product',
                [
                    'product'  =>  $product,
                    'from_warehouse_id'  =>  1,
                    'to_warehouse_id'  =>  2,
                ]
            )
        )
            ->assertStatus(200)
            ->assertJson(['success' =>  true])
            ->assertJsonStructure(
                [
                    'status', 'success',
                    'data'  =>  [
                        'product_id',
                        'name',
                        'image',
                        'thumb',
                        'source_stock',
                        'distance_stock',
                        'source_stock_details',
                    ]
                ]
            );
    }

    public function testShowProductSku()
    {
        $this->actingAs($this->admin, 'admin')->getJson(
            route(
                'admin.inventory.transactions.show-product-sku',
                [
                    'sku'  =>  'PR1',
                    'from_warehouse_id'  =>  1,
                ]
            )
        )
            ->assertStatus(200)
            ->assertJson(['success' =>  true])
            ->assertJsonStructure(
                [
                    'status', 'success',
                    'data'  =>  [
                        'remainQty',
                        'soldQty',
                        'storeQty',
                    ]
                ]
            );
    }
}
