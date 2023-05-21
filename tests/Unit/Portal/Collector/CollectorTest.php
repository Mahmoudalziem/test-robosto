<?php

namespace Tests\Unit\Portal\Collector;


use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\File;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\Schema;




use Tests\TestCase;


use Webkul\Admin\Repositories\Collector\CollectorRepository;
use Webkul\Collector\Models\Collector;
use Webkul\User\Models\Admin;
use Webkul\User\Repositories\AdminRepository;


class CollectorTest extends TestCase
{
    public $collector;
    public $admin;
    private $collectorRepository;
    private $adminRepository;

    public function setUp(): void
    {
        parent::setUp();
        $this->collectorRepository = resolve( CollectorRepository::class);
        $this->collector = Collector::find(1);
        $this->adminRepository = resolve(AdminRepository::class);
        $this->admin = $this->adminRepository->findOrFail(1);
        if (file_exists(storage_path('logs/unit-test.log'))) {
            unlink(storage_path('logs/unit-test.log'));
        }
    }

    public function testCreateCollectorWithMiddleware()
    {
        $data = [
            'area_id' => 1,
            'warehouse_id' => 1,
            'name' => "Driver Test",
            'username' => "DriverTest01",
            'email' => "DriverTest01@gmailtest.com",
            'password' => '12341234',
            'id_number' => rand(0, 100) . rand(0, 100) . rand(0, 100) . rand(0, 100),
            'liecese_validity_date' => "2020-11-11",
            'image' => "https://images.pexels.com/photos/1000084/pexels-photo-1000084.jpeg?auto=compress&cs=tinysrgb&dpr=2&h=650&w=940",
            'image_id' => "https://images.pexels.com/photos/1000084/pexels-photo-1000084.jpeg?auto=compress&cs=tinysrgb&dpr=2&h=650&w=940",
            'status' => 1,
        ];

        $response = $this->json('POST', route('admin.personal.collectors.store'), $data, ['Accept' => 'application/json']);
        $response->assertStatus(401);
        $response->assertJson(['message' => "Unauthenticated."]);
    }

    public function testCreateCollectorValidation()
    {
        $rules = [
            'area_id', 'warehouse_id', 'name', 'username', 'email', 'password', 'address',
            'phone_private', 'phone_work', 'image','image_id'];
        $data = [];

        $this->actingAs($this->admin, 'admin')->postJson(
            route('admin.personal.collectors.store'),
            $data
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors($rules, 'data.errors');

    }

    public function testCreateCollector()
    {

        $data = [
            'area_id' => 1,
            'warehouse_id' => 1,
            'name' => "Collector Test",
            'username' => "CollectorTest" . uniqid(rand(0, 100)),
            'email' => "CollectorTest" . uniqid(rand(0, 100)) . "1121@gmailtest.com",
            'password' => '12341234',
            'address' => "Address of Collector",
            'phone_private' => "01212010223",
            'phone_work' => "01212010223",
            'id_number' => rand(0, 100) . rand(0, 100) . rand(0, 100) . rand(0, 100),
            'liecese_validity_date' => "2020-11-11",
            'image' => $this->generateBase64Image(),
            'image_id' => $this->generateBase64Image(),
            'status' => 1,
        ];

        $this->actingAs($this->admin, 'admin')
            ->json('POST',
                route('admin.personal.collectors.store'),
                $data)
            ->assertStatus(200)
            ->assertJson(['status' => 200])
            ->assertJson(['success' => true])
            ->assertJsonStructure(['status', 'success', 'message', 'data'
            ]);

        $this->assertDatabaseHas('collectors', [
            'area_id' => $data['area_id'],
            'warehouse_id' => $data['warehouse_id']
        ]);
    }

    public function testUpdateCollectorValidation()
    {
        $rules = [
            'area_id', 'warehouse_id', 'name', 'username', 'email', 'address',
            'phone_private', 'phone_work'];
        $data = [];
        $collector = Collector::latest()->first();
        $this->actingAs($this->admin, 'admin')->putJson(
            route('admin.personal.collectors.update',$collector->id),
            $data
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors($rules, 'data.errors');

    }


    public function testUpdateCollector()
    {

        $collector = Collector::latest()->first();
        $data = [
            'area_id' => 1,
            'warehouse_id' => 1,
            'name' => "Collector Test",
            'username' => "CollectorTest" . uniqid(rand(0, 100)),
            'email' => "collect" . uniqid(rand(0, 100)).'@gamilc.com',
            'address' => "Address of Collector",
            'phone_private' => "01212010223" ,
            'phone_work' => "01212010223" ,
            'id_number' => rand(0, 100) . rand(0, 100) . rand(0, 100) . rand(0, 100) . rand(0, 100),

            'status' => 1,
        ];

        $response = $this->actingAs($this->admin, 'admin')
            ->json('PUT', route('admin.personal.collectors.update', $collector->id), $data);
        $response->assertStatus(200)
            ->assertJson(['status' => 200])
            ->assertJson(['success' => true])
            ->assertJsonStructure(['status', 'success', 'message', 'data'
            ]);

    }

    public function testShowCollector()
    {

        $collector = Collector::latest()->first();

        $response = $this->actingAs($this->admin, 'admin')
            ->json('GET', route('admin.personal.collectors.show', $collector->id), [], ['Accept' => 'application/json']);
        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(
                [
                    'status', 'success',
                    'data' => [
                        'id'           ,
                        'area_id'     ,
                        'area'       ,
                        'warehouse_id'      ,
                        'warehouse'       ,
                        'image'      ,
                        'image_id'      ,
                        'name'        ,
                        'username'        ,
                        'email'      ,
                        'id_number'      ,
                        'address',
                        'phone_private' ,
                        'phone_work'    ,
                        'availability',
                        'status'        ,
                        'avg_preparing_time',
                        'orders',
                        'logs',
                        'created_at'   ,
                        'updated_at'   ,
                    ]
                ]
            );
    }

    public function testListCollector()
    {

        $response = $this->actingAs($this->admin, 'admin')->json('GET', route('admin.personal.collectors.index'));
        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(
                [
                    'status', 'success',
                    'data' => [
                        0 => [
                            'id' ,
                            'area_id' ,
                            'area' ,
                            'warehouse_id' ,
                            'warehouse' ,
                            'image' ,
                            'image_id' ,
                            'id_number' ,
                            'name' ,
                            'username' ,
                            'email' ,
                            'address' ,
                            'phone_private' ,
                            'phone_work' ,
                            'availability' ,
                            'avg_preparing_time' ,
                            'orders'  ,
                            'logs' ,
                            'status'  ,
                            'created_at'  ,
                            'updated_at'  ,
                        ]
                    ]
                ]
            );


    }


    public function testSetStatusCollector()
    {

        $collector = Collector::latest()->first();
        $response = $this->actingAs($this->admin, 'admin')->json('PUT', route('admin.personal.collectors.update-status', $collector->id), ['status' => 1], ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertJson(['status' => true]);
    }


    public function testLogsLoginCollector()
    {

        $collector = Collector::latest()->first();
        $response = $this->actingAs($this->admin, 'admin')
            ->json('GET', route('admin.personal.collectors.logs', $collector->id) );
        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(
                [
                    'status', 'success',
                    'data' => [],
                ]
            );
    }



    public function testLogsOrdersCollector()
    {

        $collector = Collector::latest()->first();
        $response = $this->actingAs($this->admin, 'admin')
            ->json('GET', route('admin.personal.collectors.orders', $collector->id));
        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(
                [
                    'status', 'success',
                    'data' => [],
                ]
            );
    }



    public function testDeleteCollector()
    {

        $collector = $this->CollectorCreate();
        $response = $this->actingAs($this->admin, 'admin')->json('DELETE', route('admin.personal.collectors.delete', $collector->id), [], ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertJson(['status' => true]);
    }

    private function CollectorCreate()
    {

        $data = [
            'area_id' => 1,
            'warehouse_id' => 1,
            'name' => "Collector Test",
            'username' => "CollectorTest" . uniqid(rand(0, 100)),
            'email' => "CollectorTest" . uniqid(rand(0, 100)) . "1121@gmailtest.com",
            'password' => '12341234',
            'address' => "Address of Collector",
            'phone_private' => "01212010223",
            'phone_work' => "01212010223",
            'id_number' => rand(0, 100) . rand(0, 100) . rand(0, 100) . rand(0, 100),
            'liecese_validity_date' => "2020-11-11",
            'image' => $this->generateBase64Image(),
            'image_id' => $this->generateBase64Image(),
            'status' => 1,
        ];

        return $this->collectorRepository->create($data);
    }

}
