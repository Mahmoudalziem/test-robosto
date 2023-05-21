<?php

namespace Tests\Unit\Portal\InventoryAdjustment;

use ElasticAdapter\Support\Str;
use Tests\TestCase;
use Webkul\Admin\Http\Requests\Supplier\SupplierRequest;
use Webkul\Admin\Repositories\Inventory\InventoryTranasctionRepository;
use Webkul\Admin\Repositories\Supplier\SupplierRepository;
use Webkul\Area\Models\Area;
use Webkul\Inventory\Models\InventoryAdjustment;
use Webkul\Inventory\Models\InventoryArea;
use Webkul\Inventory\Models\InventoryProduct;
use Webkul\Inventory\Models\InventoryWarehouse;
use Webkul\Inventory\Models\Warehouse;
use Webkul\Product\Models\Product;
use Webkul\Purchase\Models\PurchaseOrder;
use Webkul\Supplier\Models\Supplier;
use Webkul\User\Repositories\AdminRepository;


class InventoryAdjustmentTest extends TestCase
{
    public $supplier;
    public $admin;
    private $inventoryTranasctionRepository;
    private $adminRepository;

    public function setUp(): void
    {
        parent::setUp();
        $this->inventoryTranasctionRepository = resolve(InventoryTranasctionRepository::class);
        $this->supplier = Supplier::find(1);
        $this->adminRepository = resolve(AdminRepository::class);
        $this->admin = $this->adminRepository->findOrFail(1);
        if (file_exists(storage_path('logs/unit-test.log'))) {
            unlink(storage_path('logs/unit-test.log'));
        }
    }

    public function testCreateInventoryAdjustmentValidationWithoutData()
    {
        $rules = [
            'warehouse_id',
            'products',
        ];
        $data = [];

        $this->actingAs($this->admin, 'admin')->postJson(
            route('admin.inventory.adjustments.store')
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors($rules, 'data.errors');

    }

    public function testCreateInventoryAdjustmentValidationWithSomeData()
    {
        $data = [
            'products' => [
                0 => [

                    'skus' =>
                        [
                            [
                                'inventory_product_id' => null,
                                'product_id' => null,
                                'qty' => 0,
                                'sku' => null,
                                'status' => 1,
                            ]
                        ]
                ]
            ]
        ];

        $this->actingAs($this->admin, 'admin')->postJson(
            route('admin.inventory.adjustments.store'),
            $data
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'products.0.skus.0.qty'
            ], 'data.errors');
    }

    public function testCreateInventoryAdjustmenValidationWithLessQuantity()
    {
        $data = [
            'warehouse_id' => 2,
            'products' => [
                0 => [
                    'skus' =>
                        [
                            [
                                'product_id' => 1,
                                'qty' => 600,
                                'sku' => 'PR1',
                                'status' => 1,
                            ]
                        ]
                ]
            ]
        ];

        $this->actingAs($this->admin, 'admin')->postJson(
            route('admin.inventory.adjustments.store'),
            $data
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'products.0.skus.0.qty'
            ], 'data.errors');
    }


    public function testCreateInventoryAdjustment()
    {
        $warehouse = Warehouse::find(1);
        $area = Area::find($warehouse->id);
        $adjustment = InventoryProduct::where(['warehouse_id' => $warehouse->id, 'area_id' => $area->id])->first();

        $inventoryAdjustmentCountBeforeCreate = InventoryAdjustment::count();

        $data = [
            "warehouse_id" => $adjustment->warehouse_id,
            "products" => [
                0 => [
                    "skus" => [
                        0 =>
                            [
                                "product_id" => $adjustment->product_id,
                                "sku" => $adjustment->sku,
                                "qty" => 5,
                                "image" => "",
                                "status" => 1,
                                "note" => "note 0"
                            ],
                        1 =>
                            [
                                "product_id" => $adjustment->product_id,
                                "sku" => $adjustment->sku,
                                "qty" => 5,
                                "image" => "",
                                "status" => 2,
                                "note" => "note 0"
                            ],
                        2 =>
                            [
                                "product_id" => $adjustment->product_id,
                                "sku" => $adjustment->sku,
                                "qty" => 5,
                                "image" => "",
                                "status" => 3,
                                "note" => "note 0"
                            ],
                        3 =>
                            [
                                "product_id" => $adjustment->product_id,
                                "sku" => $adjustment->sku,
                                "qty" => 5,
                                "image" => "",
                                "status" => 4,
                                "note" => "note 0"
                            ]
                    ]

                ],

            ],

        ];

        $response = $this->actingAs($this->admin, 'admin')
            ->json('POST',
                route('admin.inventory.adjustments.store'),
                $data);
        $response->assertStatus(200)
            ->assertJson(['status' => 200])
            ->assertJson(['success' => true])
            ->assertJsonStructure(['status', 'success', 'data'
            ]);
        $newInventoryAdjustmentID = $response['data']['id'];


        $inventoryAdjustment = InventoryAdjustment::find($newInventoryAdjustmentID);

        $this->assertDatabaseCount('inventory_adjustments', $inventoryAdjustmentCountBeforeCreate + 1);
        $this->assertDatabaseHas('inventory_adjustments', [
            'id' => $newInventoryAdjustmentID,
            'warehouse_id' => $inventoryAdjustment->warehouse_id,

        ]);
        $inventoryAdjustment = InventoryAdjustment::find($newInventoryAdjustmentID);

        foreach ($inventoryAdjustment->adjustmentProducts as $k => $products) {
            $this->assertDatabaseHas('inventory_adjustment_products', [
                'inventory_adjustment_id' => $newInventoryAdjustmentID,
                'product_id' => $data['products'][0]['skus'][$k]['product_id'],
                'sku' => $data['products'][0]['skus'][$k]['sku'],
                'qty' => $data['products'][0]['skus'][$k]['qty'],
                'status' => $data['products'][0]['skus'][$k]['status'],
            ]);


        }


    }


    public function testInventoryAdjustmentsSetStatusApproved()
    {

        $adjustment = InventoryAdjustment::latest()->first();
        $area = Warehouse::find($adjustment->warehouse_id)->area;
        $inventoryProductRow = InventoryProduct::where(['sku' => $adjustment->adjustmentProducts->first()->sku, 'warehouse_id' => $adjustment->warehouse_id])->first();
        $inventoryWarehouseRow = InventoryWarehouse::where(['warehouse_id' => $adjustment->warehouse_id, 'product_id' => $adjustment->adjustmentProducts->first()->product_id])->first();
        $inventoryAreaRow = InventoryArea::where(['area_id' => $area->id, 'product_id' => $adjustment->adjustmentProducts->first()->product_id])->first();

        $status = 2; // Approved
        $response = $this->actingAs($this->admin, 'admin')->json('PUT', route('admin.inventory.adjustments.update-status', $adjustment->id), ['status' => $status]);
        $response->assertStatus(200)->assertJson(['status' => true]);
        // 4 cases [1 = Lost => decrease ,2 = Expired => decrease ,3 = Over Qty => incrase ,4 = Damaged => decrease ]

        $qty = 0;
        foreach ($adjustment->adjustmentProducts as $row) {
            //  [1 = Lost ,2 = Expired ,4 = Damaged ] => decrease
            if ($row->status == 1 || $row->status == 2 || $row->status == 4) {
                // inventory Products
                $qty = $qty - $row['qty'];
            }
            //  [3 = Over Qty ] => increase
            if ($row->status == 3) {
                // inventory Products
                $qty = $qty + $row['qty'];

            }

            ////////////////////////////////////////////////////////////////////

        }


        $this->assertDatabaseHas('inventory_products', [
            'product_id' => $inventoryProductRow->product_id,
            'sku' => $inventoryProductRow->sku,
            'warehouse_id' => $inventoryProductRow->warehouse_id,
            'area_id' => $inventoryProductRow->area_id,
            'qty' => $inventoryProductRow->qty + $qty, // 100 -5 -5

        ]);
        $this->assertDatabaseHas('inventory_warehouses', [
            'product_id' => $inventoryWarehouseRow->product_id,
            'area_id' => $inventoryWarehouseRow->area_id,
            'warehouse_id' => $inventoryWarehouseRow->warehouse_id,
            'qty' => $inventoryWarehouseRow->qty + $qty
        ]);
        $this->assertDatabaseHas('inventory_areas', [
            'product_id' => $inventoryAreaRow->product_id,
            'area_id' => $inventoryAreaRow->area_id,
            'total_qty' => $inventoryAreaRow->total_qty + $qty
        ]);

    }

    public function testShowInventoryAdjustment()
    {

        $adjustment = InventoryAdjustment::latest()->first();
        $response = $this->actingAs($this->admin, 'admin')
            ->json('GET', route('admin.inventory.adjustments.show', $adjustment->id));

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(
                [
                    'status', 'success',
                    'data' => [
                        "id",
                        "warehouse_id",
                        "warehouse",
                        "Area",
                        "status",
                        "statusName",
                        "products",
                        "created_at"
                    ]
                ]
            );
    }

//
    public function testListInventoryAdjustment()
    {

        $response = $this->actingAs($this->admin, 'admin')->json('GET', route('admin.inventory.adjustments.index'));
        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(
                [
                    'status', 'success',
                    'data' => [
                        0 => [
                            "id",
                            "warehouse",
                            "status",
                            "statusName",
                            "area",
                            "created_at"
                        ]
                    ]
                ]
            );


    }

//    public function testProductsSearch()
//    {
//       $response= $this->actingAs($this->admin, 'admin')->getJson(
//            route(
//                'admin.inventory.adjustments.products-search',
//                [
//                    'key' => 'a',
//                    'warehouse_id' => 1,
//                ]
//            )
//        );
//       
//           $response ->assertStatus(200)
//            ->assertJson(['success' => true])
//            ->assertJsonStructure(
//                [
//                    'status', 'success',
//                    'data' => [
//                        0 => [
//                            "id",
//                            "barcode",
//                            "prefix",
//                            "featured",
//                            "status",
//                            "price",
//                            "sold_count",
//                            "visits_count",
//                            "image_url",
//                            "name",
//
//                        ]
//                    ]
//                ]
//            );
//    }

    public function testSelectProduct()
    {
        $product = Product::first();
        $this->actingAs($this->admin, 'admin')->getJson(
            route(
                'admin.inventory.adjustments.select-product',
                [
                    'product'  =>  $product,
                    'warehouse_id'  =>  1,
                ]
            )
        )
            ->assertStatus(200)
            ->assertJson(['success' =>  true])
            ->assertJsonStructure(
                [
                    'status', 'success',
                    'data'  =>  [
                        "product_id" ,
                        "name" ,
                        "image" ,
                        "source_stock" ,
                        "source_stock_details"
                    ]
                ]
            );
    }

    public function testShowProductSku()
    {
        $this->actingAs($this->admin, 'admin')->getJson(
            route(
                'admin.inventory.adjustments.show-product-sku',
                [
                    'sku'  =>  'PR1',
                    'warehouse_id'  =>  1,
                    'inventory_adjustment_product_id' =>1
                ]
            )
        )
            ->assertStatus(200)
            ->assertJson(['success' =>  true])
            ->assertJsonStructure(
                [
                    'status', 'success',
                    'data'  =>  [
                        "remainQty",
                        "soldQty",
                        "storeQty",
                        "image",
                        "sku_status",
                    ]
                ]
            );
    }
}
