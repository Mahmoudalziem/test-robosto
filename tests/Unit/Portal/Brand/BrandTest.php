<?php

namespace Tests\Unit\Portal\Brand;

use ElasticAdapter\Support\Str;
use Tests\TestCase;
use Webkul\Admin\Repositories\Brand\BrandRepository;
use Webkul\Brand\Models\Brand;
use Webkul\User\Repositories\AdminRepository;


class BrandTest extends TestCase
{
    public $brand;
    public $admin;
    private $brandRepository;
    private $adminRepository;

    public function setUp(): void
    {
        parent::setUp();
        $this->brandRepository = resolve(BrandRepository::class);
        $this->brand = Brand::find(1);
        $this->adminRepository = resolve(AdminRepository::class);
        $this->admin = $this->adminRepository->findOrFail(1);
        if (file_exists(storage_path('logs/unit-test.log'))) {
            unlink(storage_path('logs/unit-test.log'));
        }
    }

    public function testCreateBrandValidation()
    {
        $rules = [
            'image', 'prefix', 'ar.name', 'en.name'];
        $data = [];

        $this->actingAs($this->admin, 'admin')->postJson(
            route('admin.app-management.brands.store'),
            $data
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors($rules, 'data.errors');

    }

    public function testCreateBrand()
    {

        $ar_name = "Brand Test Ar" . uniqid(rand(0, 100));
        $en_name = "Brand Test en" . uniqid(rand(0, 100));
        $prefix = \Illuminate\Support\Str::random(2);
        $data = [
            'image' => $this->generateBase64Image(),
            'prefix' => $prefix,
            'ar' => [
                'name' => $ar_name,
            ],
            'en' => [
                'name' => $en_name,
            ],
            'position' => rand(0, 100),
        ];

        $this->actingAs($this->admin, 'admin')
            ->json('POST',
                route('admin.app-management.brands.store'),
                $data)
            ->assertStatus(200)
            ->assertJson(['status' => 200])
            ->assertJson(['success' => true])
            ->assertJsonStructure(['status', 'success', 'data'
            ]);
        $brand = Brand::latest()->first();
        $this->assertDatabaseHas('brands', [
            'id' => $brand->id,
            'prefix' => $data['prefix'],
            'position' => $data['position'],
        ]);
        $this->assertDatabaseHas('brand_translations', [
            'name' => $ar_name,
            'locale' => "ar",
        ]);

        $this->assertDatabaseHas('brand_translations', [

            'name' => $en_name,
            'locale' => "en",

        ]);
    }

    public function testUpdateBrandValidation()
    {
        $rules = [
            'prefix', 'ar.name', 'en.name'];
        $data = [];
        $brand = Brand::latest()->first();
        $this->actingAs($this->admin, 'admin')->postJson(
            route('admin.app-management.brands.update', $brand->id),
            $data
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors($rules, 'data.errors');

    }


    public function testUpdateBrand()
    {

        $brand = Brand::latest()->first();
        $ar_name = "Brand Test Ar" . uniqid(rand(0, 100));
        $en_name = "Brand Test en" . uniqid(rand(0, 100));
        $prefix = \Illuminate\Support\Str::random(2);
        $data = [
            'prefix' => $prefix,
            'ar' => [
                'name' => $ar_name,
            ],
            'en' => [
                'name' => $en_name,
            ],
            'position' => rand(0, 100),
        ];

        $response = $this->actingAs($this->admin, 'admin')
            ->json('POST', route('admin.app-management.brands.update', $brand->id), $data);
        $response->assertStatus(200)
            ->assertJson(['status' => 200])
            ->assertJson(['success' => true])
            ->assertJsonStructure(['status', 'success', 'data'
            ]);

    }

    public function testShowBrand()
    {

        $brand = Brand::latest()->first();

        $response = $this->actingAs($this->admin, 'admin')
            ->json('GET', route('admin.app-management.brands.show', $brand->id));
        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(
                [
                    'status', 'success',
                    'data' => [
                        "id",
                        "position",
                        "prefix",
                        "image",
                        "status",
                        "created_at",
                        "updated_at",
                        "image_url",
                        "name",
                        "translations"
                    ]]
            );
    }

    public function testListBrand()
    {

        $response = $this->actingAs($this->admin, 'admin')->json('GET', route('admin.app-management.brands.index'));
        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(
                [
                    'status', 'success',
                    'data' => [
                        0 => [
                            'id',
                            'name',
                            'prefix',
                            'image',
                            'image_url',
                            'status',
                            'products',
                            'created_at',
                        ]
                    ]
                ]
            );


    }

    public function testGetAllBrands()
    {
        $response = $this->actingAs($this->admin, 'admin')->json('GET', route('admin.app-management.brands.list-all'));
        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(
                [
                    'status', 'success',
                    'data' => [
                        0 => [
                            "id",
                            "position",
                            "prefix",
                            "image",
                            "status",
                            "created_at",
                            "updated_at",
                            "image_url",
                            "name",
                            "translations"
                        ]]]
            );
    }

    public function testProductsByBrandId()
    {
        $brand = Brand::first();
        $response = $this->actingAs($this->admin, 'admin')->json('GET', route('admin.app-management.brands.products',$brand->id));
        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(
                [
                    'status', 'success',
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
                            'unit_name',
                            'name',
                            'shelve',
                            'unit',
                            'brand',
                            'suppliers',
                            'areas',
                            'warehouses',
                            'inventoryProducts',
                            'subCategories',
                            'created_at',
                            'updated_at',
                        ]]]
            );
    }

    public function testSetStatusBrand()
    {

        $brand = Brand::latest()->first();
        $response = $this->actingAs($this->admin, 'admin')->json('POST', route('admin.app-management.brands.update-status', $brand->id), ['status' => 1], ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertJson(['status' => true]);
    }


    public function testDeleteUpdate()
    {

        $brand = $this->brandCreate();
        $response = $this->actingAs($this->admin, 'admin')->json('POST', route('admin.app-management.brands.delete', $brand->id));
        $response->assertStatus(200);
        $response->assertJson(['status' => true]);
    }

    private function brandCreate()
    {

        $ar_name = "Brand Test Ar" . uniqid(rand(0, 100));
        $en_name = "Brand Test en" . uniqid(rand(0, 100));
        $prefix = \Illuminate\Support\Str::random(2);
        $data = [
            'image' => $this->generateBase64Image(),
            'prefix' => $prefix,
            'ar' => [
                'name' => $ar_name,
            ],
            'en' => [
                'name' => $en_name,
            ],
            'position' => rand(0, 100),
        ];

        return $this->brandRepository->create($data);
    }

}
