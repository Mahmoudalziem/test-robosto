<?php

namespace Tests\Unit\Portal\Supplier;

use ElasticAdapter\Support\Str;
use Tests\TestCase;
use Webkul\Admin\Http\Requests\Supplier\SupplierRequest;
use Webkul\Admin\Repositories\Supplier\SupplierRepository;
use Webkul\Supplier\Models\Supplier;
use Webkul\User\Repositories\AdminRepository;


class SupplierTest extends TestCase
{
    public $supplier;
    public $admin;
    private $supplierRepository;
    private $adminRepository;

    public function setUp(): void
    {
        parent::setUp();
        $this->supplierRepository = resolve(SupplierRepository::class);
        $this->supplier = Supplier::find(1);
        $this->adminRepository = resolve(AdminRepository::class);
        $this->admin = $this->adminRepository->findOrFail(1);
        if (file_exists(storage_path('logs/unit-test.log'))) {
            unlink(storage_path('logs/unit-test.log'));
        }
    }

    public function testCreateSupplierValidationWithoutData()
    {
        $rules = [
            'name', 'email', 'company_name', 'address_title', 'address_city',
            'address_state', 'address_zip', 'address_phone', 'areas', 'products',

        ];
        $data = [];

        $this->actingAs($this->admin, 'admin')->postJson(
            route('admin.inventory.suppliers.store'),
            $data, ['Accept' => 'application/json']
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors($rules, 'data.errors');

    }

    public function testCreateSupplierValidationWithSomeData()
    {
        $rules = [
            'name', 'email', 'company_name', 'address_title', 'address_city',
            'address_state', 'address_zip', 'address_phone', 'areas', 'products.0.product_id', 'products.0.brand_id'

        ];
        $data = [
            'products'  =>  [
                ['product_id', 'brand_id',]
            ]
        ];

        $this->actingAs($this->admin, 'admin')->postJson(
            route('admin.inventory.suppliers.store'),
            $data, ['Accept' => 'application/json']
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors($rules, 'data.errors');

    }

    public function testCreateSupplier()
    {
        $data = [
            'name' => "Supplier Name " . uniqid(rand(0, 100)),
            'email' => "supplier0001@gmail.com",
            'work_phone' => "01154857542" . uniqid(rand(0, 100)),
            'mobile_phone' => "01154857542" . uniqid(rand(0, 100)),
            'areas' => [1, 2],
            'company_name' => "Company Supplier Name " . uniqid(rand(0, 100)),
            'address_title' => "Address Supplier Name " . uniqid(rand(0, 100)),
            'address_city' => "address city Supplier Name " . uniqid(rand(0, 100)),
            'address_state' => "address state Supplier Name " . uniqid(rand(0, 100)),
            'address_zip' => 62621,
            'address_phone' => "01154857542" . uniqid(rand(0, 100)),
            'address_fax' => 76543,
            'remarks' => "good supplier",
            'products' => [
                ["product_id" => 1, "brand_id" => 5], ["product_id" => 2, "brand_id" => 5], ["product_id" => 3, "brand_id" => 8]
            ],

        ];

        $this->actingAs($this->admin, 'admin')
            ->json('POST',
                route('admin.inventory.suppliers.store'),
                $data)
            ->assertStatus(200)
            ->assertJson(['status' => 200])
            ->assertJson(['success' => true])
            ->assertJsonStructure(['status', 'success', 'data'
            ]);
        $supplier = Supplier::latest()->first();
        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'name' => $data['name'],

        ]);

        $this->assertDatabaseHas('supplier_products', [
            'supplier_id' => $supplier->id,
            'product_id' => $data['products'][0]['product_id'],
            'brand_id' => $data['products'][0]['brand_id'],
        ]);

        $this->assertDatabaseHas('supplier_areas', [
            'supplier_id' => $supplier->id,
            'area_id' => $data['areas'],

        ]);

    }

    public function testUpdateSupplierValidation()
    {
        $rules = [
            'name', 'email', 'company_name', 'address_title', 'address_city',
            'address_state', 'address_zip', 'address_phone', 'areas', 'products',
        ];
        $data = [];
        $supplier = Supplier::latest()->first();
        $this->actingAs($this->admin, 'admin')->postJson(
            route('admin.inventory.suppliers.update', $supplier->id),
            $data
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors($rules, 'data.errors');

    }


    public function testUpdateSupplier()
    {

        $supplier = Supplier::latest()->first();

        $data = [
            'name' => "Supplier Name " . uniqid(rand(0, 100)),
            'email' => "supplier0001@gmail.com",
            'work_phone' => "01154857542" . uniqid(rand(0, 100)),
            'mobile_phone' => "01154857542" . uniqid(rand(0, 100)),
            'areas' => [1, 2],
            'company_name' => "Company Supplier Name " . uniqid(rand(0, 100)),
            'address_title' => "Address Supplier Name " . uniqid(rand(0, 100)),
            'address_city' => "address city Supplier Name " . uniqid(rand(0, 100)),
            'address_state' => "address state Supplier Name " . uniqid(rand(0, 100)),
            'address_zip' => 62621,
            'address_phone' => "01154857542" . uniqid(rand(0, 100)),
            'address_fax' => 76543,
            'remarks' => "good supplier",
            'products' => [
                ["product_id" => 1, "brand_id" => 5], ["product_id" => 2, "brand_id" => 5], ["product_id" => 3, "brand_id" => 8]
            ],

        ];
        $response = $this->actingAs($this->admin, 'admin')
            ->json('POST', route('admin.inventory.suppliers.update', $supplier->id), $data);
        $response->assertStatus(200)
            ->assertJson(['status' => 200])
            ->assertJson(['success' => true])
            ->assertJsonStructure(['status', 'success', 'data'
            ]);

    }

   
    public function testShowSupplier()
    {

        $supplier = Supplier::latest()->first();

        $response = $this->actingAs($this->admin, 'admin')
            ->json('GET', route('admin.inventory.suppliers.show', $supplier->id));
        
        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(
                [
                    'status', 'success',
                    'data' => [
                        "data"=>[
                            "id",
                            "name",
                            "email",
                            "work_phone",
                            "mobile_phone",
                            "company_name",
                            "address_title",
                            "address_city",
                            "address_state",
                            "address_zip",
                            "address_phone",
                            "address_fax",
                            "remarks",
                            "status",
                            "created_at",
                            "updated_at",
                            "areas",
                           // "products",
                        ]
                         
                    ]
                ]
            );
    }

    public function testListSupplier()
    {

        $response = $this->actingAs($this->admin, 'admin')->json('GET', route('admin.inventory.suppliers.index'));
        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(
                [
                    'status', 'success',
                    'data' => [
                        0 => [
                            'id'          ,
                            'name'        ,
                            'email'        ,
                            'work_phone'         ,
                            'mobile_phone'        ,
                            'company_name'        ,
                            'address_title'     ,
                            'address_city'      ,
                            'address_state'        ,
                            'address_zip'       ,
                            'address_phone'      ,
                            'address_fax'      ,
                            'remarks'       ,
                            'country'        ,
                            'areas'        ,
                            'status'        ,
                            'created_at'    ,
                        ]
                    ]
                ]
            );


    }

    public function testSetStatusSupplier()
    {

        $supplier = Supplier::latest()->first();
        $response = $this->actingAs($this->admin, 'admin')->json('POST', route('admin.inventory.suppliers.update-status', $supplier->id), ['status' => 1], ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertJson(['status' => true]);
    }

    public function testSupplierProductDelete()
    {
        $supplier = Supplier::latest()->first();
        $response = $this->actingAs($this->admin, 'admin')->json('POST', route('admin.inventory.suppliers.product.delete', [$supplier->id,1]));
        $response->assertStatus(200);
        $response->assertJson(['status' => true]);
    }
    
    public function testDeleteSupplier()
    {

         $supplier = Supplier::latest()->first();
        $response = $this->actingAs($this->admin, 'admin')->json('POST', route('admin.inventory.suppliers.delete', $supplier->id));
        $response->assertStatus(200);
        $response->assertJson(['status' => true]);
    }



}
