<?php

namespace Tests\Unit\Portal\Product;

use Tests\TestCase;
use Illuminate\Support\Str;
use Webkul\User\Models\Admin;
use Illuminate\Support\Facades\Event;
use Webkul\Brand\Models\Brand;
use Webkul\Category\Models\SubCategory;
use Webkul\Core\Models\Shelve;
use Webkul\Inventory\Models\Warehouse;
use Webkul\Product\Models\Product;
use Webkul\Product\Models\Unit;

class ProductTest extends TestCase {

    private $admin;

    public function setUp(): void {
        parent::setUp();
        $this->admin = Admin::find(1);
    }

    public function testGettingProducts() {

        $this->actingAs($this->admin, 'admin')->getJson(route('admin.app-management.products.index'))
                ->assertStatus(200)
                ->assertJson(['success' => true])
                ->assertJsonStructure(
                        [
                            'status', 'success', 'message',
                            'data' => [
                                0 => [
                                    'id',
                                    'barcode',
                                    'prefix',
                                    'image',
                                    'image_url',
                                    'thumb_url',
                                    'featured',
                                    'status',
                                    'returnable',
                                    'price',
                                    'cost',
                                    'tax',
                                    'weight',
                                    'width',
                                    'height',
                                    'length',
                                    'brand_id',
                                    'unit_id',
                                    'unit_value',
                                    'name',
                                    'shelve',
                                    'unit',
                                    'brand',
                                    'suppliers',
                                    'areas',
                                    'warehouses',
                                    'inventoryProducts',
                                    'subCategories',
                                    'tags',
                                    'created_at',
                                    'updated_at',
                                ]
                            ]
                        ]
        );
    }

    public function testGettingUnits() {
        $this->actingAs($this->admin, 'admin')->getJson(route('admin.app-management.products.list-units'))
                ->assertStatus(200)
                ->assertJson(['success' => true])
                ->assertJsonStructure(
                        [
                            'status', 'success',
                            'data' => [
                                0 => [
                                    'id',
                                    'name',
                                    'measure',
                                    'status',
                                    'created_at',
                                    'updated_at'
                                ]
                            ]
                        ]
        );
    }

    public function testShowProduct() {
        $product = Product::first();

        $this->actingAs($this->admin, 'admin')->getJson(
                        route('admin.app-management.products.show', $product)
                )
                ->assertStatus(200)
                ->assertJson(['success' => true])
                ->assertJsonStructure([
                    'status',
                    'success',
                    'data' => [
                        'id',
                        'barcode',
                        'prefix',
                        'image',
                        'image_url',
                        'thumb_url',
                        'featured',
                        'status',
                        'returnable',
                        'price',
                        'cost',
                        'tax',
                        'weight',
                        'width',
                        'height',
                        'length',
                        'brand_id',
                        'unit_id',
                        'unit_value',
                        'name',
                        'shelve',
                        'unit',
                        'brand',
                        'suppliers',
                        'areas',
                        'warehouses',
                        'inventoryProducts',
                        'subCategories',
                        'tags',
                        'created_at',
                        'updated_at',
                    ]
        ]);
    }

    public function testGetSKUs() {
        $product = Product::first();
        $warehouses = [1, 2];

        $this->actingAs($this->admin, 'admin')->getJson(
                        route('admin.app-management.products.skus', $product)
                )
                ->assertStatus(200)
                ->assertJson(['success' => true])
                ->assertJsonStructure([
                    'status',
                    'success',
                    'data' => [
                        0 => [
                            "id",
                            "sku",
                            "prod_date",
                            "exp_date",
                            "qty",
                            "cost_before_discount",
                            "cost",
                            "amount_before_discount",
                            "amount",
                            "product_id",
                            "warehouse_id",
                            "area_id",
                            "created_at",
                            "updated_at",
                            "price",
                            "product",
                        ]
                    ]
        ]);
    }

    public function testGetSKUsForWarehouses() {
        $product = Product::first();
        $warehouses = "[1, 2]";

        $response = $this->actingAs($this->admin, 'admin')->getJson(
                route('admin.app-management.products.skus', ['id' => $product->id, 'warehouses' => $warehouses])
        );

        $response
                ->assertStatus(200)
                ->assertJson(['success' => true])
                ->assertJsonStructure([
                    'status',
                    'success',
                    'data' => [
                        0 => [
                            "id",
                            "sku",
                            "prod_date",
                            "exp_date",
                            "qty",
                            "cost_before_discount",
                            "cost",
                            "amount_before_discount",
                            "amount",
                            "product_id",
                            "warehouse_id",
                            "area_id",
                            "created_at",
                            "updated_at",
                            "price",
                            "product",
                        ]
                    ]
        ]);
    }

    public function testCreateProductValidation() {
        $data = [];

        $this->actingAs($this->admin, 'admin')->postJson(
                        route('admin.app-management.products.store'),
                        $data
                )
                ->assertStatus(422)
                ->assertJsonValidationErrors([
                    'barcode', 'prefix', 'returnable', 'price', 'weight', 'width', 'height', 'length', 'shelve_id',
                    'brand_id', 'unit_id', 'unit_value', 'sub_categories', 'image', 'ar.name', 'en.name',
                    'ar.description', 'en.description'
                        ], 'data.errors');
    }

    public function testCreateProduct() {
        $productsCountBeforeCreate = Product::count();
        $arName = "Arabic Name " . Str::random(4);
        $arDesc = "Arabic Description " . Str::random(10);
        $enName = "English Name " . Str::random(4);
        $enDesc = "English Description " . Str::random(10);

        $data = [
            'barcode' => 'test12345',
            'prefix' => 'AN',
            'image' => $this->generateBase64Image(),
            'returnable' => 1,
            'price' => 16.5,
            'weight' => 0.75,
            'width' => 0.19,
            'height' => 15,
            'length' => 20,
            'shelve_id' => Shelve::first()->id,
            'brand_id' => Brand::first()->id,
            'unit_id' => Unit::first()->id,
            'unit_value' => 2,
            'sub_categories' => SubCategory::limit(2)->get()->pluck('id')->toArray(),
            'ar' => [
                'name' => $arName,
                'description' => $arDesc,
            ],
            'en' => [
                'name' => $enName,
                'description' => $enDesc,
            ],
        ];

        $response = $this->actingAs($this->admin, 'admin')->postJson(
                route('admin.app-management.products.store'),
                $data
        );
        $response
                ->assertStatus(200)
                ->assertJson(['status' => 200])
                ->assertJsonStructure([
                    'status', 'success', 'data'
        ]);

        $newProductID = $response['data']['id'];

        $this->assertDatabaseCount('products', $productsCountBeforeCreate + 1);
        $this->assertDatabaseHas('products', [
            'id' => $newProductID,
            'barcode' => 'test12345',
            'prefix' => 'AN',
        ]);
        $this->assertDatabaseHas('product_translations', [
            'locale' => 'ar',
            'name' => $arName,
            'description' => $arDesc,
        ]);

        $this->assertDatabaseHas('product_translations', [
            'locale' => 'en',
            'name' => $enName,
            'description' => $enDesc,
        ]);
    }

    public function testUpdateProductValidation() {
        $productID = Product::latest()->first()->id;
        $data = [];

        $this->actingAs($this->admin, 'admin')->postJson(
                        route('admin.app-management.products.update', $productID),
                        $data
                )
                ->assertStatus(422)
                ->assertJsonValidationErrors([
                    'barcode', 'prefix', 'returnable', 'price', 'weight', 'width', 'height', 'length', 'shelve_id',
                    'brand_id', 'unit_id', 'unit_value', 'sub_categories', 'ar.name', 'en.name',
                    'ar.description', 'en.description'
                        ], 'data.errors');
    }

    public function testUpdateProduct() {
        $product = Product::latest()->first();
        $productID = $product->id;


        $arName = "Arabic Name Updated";
        $arDesc = "Arabic Description " . Str::random(10);
        $enName = "English Name Updated";
        $enDesc = "English Description " . Str::random(10);

        $data = [
            'barcode' => 'test-updated12345',
            'prefix' => 'AN',
            'returnable' => 1,
            'price' => 16.5,
            'weight' => 0.75,
            'width' => 0.19,
            'height' => 15,
            'length' => 20,
            'shelve_id' => $product->shelve_id,
            'brand_id' => $product->brand_id,
            'unit_id' => $product->unit_id,
            'unit_value' => 2,
            'sub_categories' => $product->subCategories->pluck('id')->toArray(),
            'ar' => [
                'name' => $arName,
                'description' => $arDesc,
            ],
            'en' => [
                'name' => $enName,
                'description' => $enDesc,
            ],
        ];

        $response = $this->actingAs($this->admin, 'admin')->postJson(
                route('admin.app-management.products.update', $productID),
                $data
        );

        $response
                ->assertStatus(200)
                ->assertJson(['status' => 200])
                ->assertJsonStructure([
                    'status', 'success', 'data'
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $productID,
            'barcode' => 'test-updated12345',
        ]);

        $this->assertDatabaseHas('product_translations', [
            'product_id' => $productID,
            'locale' => 'ar',
            'name' => $arName,
        ]);

        $this->assertDatabaseHas('product_translations', [
            'product_id' => $productID,
            'locale' => 'en',
            'name' => $enName,
        ]);
    }

    public function testUpdateProductStatus() {
        $productID = Product::latest()->first()->id;
        $data = [
            'status' => 0
        ];

        $this->actingAs($this->admin, 'admin')->postJson(
                        route('admin.app-management.products.update-status', $productID),
                        $data
                )
                ->assertStatus(200)
                ->assertJson(['status' => 200])
                ->assertJsonStructure([
                    'status', 'success', 'data'
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $productID,
            'status' => 0,
        ]);
    }

 

}
