<?php

namespace Tests\Unit\Portal\Driver;


use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\File;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\Schema;

use Webkul\Admin\Repositories\Driver\DriverRepository;


use Tests\TestCase;

use Webkul\Driver\Models\Driver;
use Webkul\User\Models\Admin;
use Webkul\User\Repositories\AdminRepository;


class DriverTest extends TestCase
{
    public $driver;
    public $admin;
    private $driverRepository;
    private $adminRepository;

    public function setUp(): void
    {
        parent::setUp();
        $this->driverRepository = resolve(DriverRepository::class);
        $this->driver = Driver::find(1);
        $this->adminRepository = resolve(AdminRepository::class);
        $this->admin = $this->adminRepository->findOrFail(1);
        if (file_exists(storage_path('logs/unit-test.log'))) {
            unlink(storage_path('logs/unit-test.log'));
        }
    }

    public function testCreateDriverWithMiddleware()
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

        $response = $this->json('POST', route('admin.personal.drivers.store'), $data, ['Accept' => 'application/json']);
        $response->assertStatus(401);
        $response->assertJson(['message' => "Unauthenticated."]);
    }

    public function testCreateDriverValidation()
    {
        $rules = [
            'area_id', 'warehouse_id', 'name', 'username',  'email','password', 'address',
            'phone_private', 'phone_work', 'image', 'image_id'];
        $data = [];

        $this->actingAs($this->admin, 'admin')->postJson(
            route('admin.personal.drivers.store'),
            $data
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors($rules, 'data.errors');

    }

    public function testCreateDriver()
    {

        $data = [
            'area_id' => 1,
            'warehouse_id' => 1,
            'name' => "Driver Test",
            'username' => "DriverTest" . uniqid(rand(0, 100)),
            'email' => "DriverTest" . uniqid(rand(0, 100)) . "1121@gmailtest.com",
            'password' => '12341234',
            'address' => "Address of driver",
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
                route('admin.personal.drivers.store'),
                $data)
            ->assertStatus(200)
            ->assertJson(['status' => 200])
            ->assertJson(['message' => 'New Dirver has been created!'])
            ->assertJson(['success' => true])
            ->assertJsonStructure(['status', 'success', 'message', 'data'
            ]);

        $this->assertDatabaseHas('drivers', [
            'area_id' => $data['area_id'],
            'warehouse_id' => $data['warehouse_id']
        ]);
    }

    public function testUpdateDriverValidation()
    {
        $rules = [
            'area_id', 'warehouse_id', 'name', 'username', 'email', 'address',
            'phone_private', 'phone_work'];
        $data = [];
        $driver = Driver::latest()->first();

        $this->actingAs($this->admin, 'admin')->putJson(
            route('admin.personal.drivers.update',$driver->id),
            $data
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors($rules, 'data.errors');

    }


    public function testUpdateDriver()
    {

        $driver = Driver::latest()->first();
        $data = [
            'area_id' => 1,
            'warehouse_id' => 1,
            'name' => "Driver Test",
            'username' => "DriverTest" . uniqid(rand(0, 100)),
            'email' => "driver" . uniqid(rand(0, 100)).'@gamilc.com',
            'address' => "Address of driver",
            'phone_private' => "01212010223" ,
            'phone_work' => "01212010223" ,
            'id_number' => rand(0, 100) . rand(0, 100) . rand(0, 100) . rand(0, 100) . rand(0, 100),
            'liecese_validity_date' => "2020-11-11",
            'status' => 1,
        ];

        $response = $this->actingAs($this->admin, 'admin')->json('PUT', route('admin.personal.drivers.update', $driver->id), $data);
        $response->assertStatus(200)
            ->assertJson(['status' => 200])
            ->assertJson(['message' => 'Driver has been updated!'])
            ->assertJson(['success' => true])
            ->assertJsonStructure(['status', 'success', 'message', 'data'
            ]);

    }

    public function testShowDriver()
    {

        $driver = Driver::latest()->first();

        $response = $this->actingAs($this->admin, 'admin')->json('GET', route('admin.personal.drivers.show', $driver->id), [], ['Accept' => 'application/json']);
        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(
                [
                    'status', 'success',
                    'data' => [
                        'id',
                        'area_id',
                        'area',
                        'warehouse_id',
                        'warehouse',
                        'image',
                        'image_id',
                        'name',
                        'id_number',
                        'username',
                        'email',
                        'address',
                        'phone_private',
                        'phone_work',
                        'liecese_validity_date',
                        'license_plate_no',
                        'availability',
                        'status',
                        'avg_delivery_time', //mins
                        'wallet',
                        'total_wallet',
                        'created_at',
                        'updated_at',
                    ]
                ]
            );
    }

    public function testListDriver()
    {

        $response = $this->actingAs($this->admin, 'admin')->json('GET', route('admin.personal.drivers.index'));
        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(
                [
                    'status', 'success',
                    'data' => [
                        0 => [
                            'id',
                            'area_id',
                            'area',
                            'warehouse_id',
                            'warehouse',
                            'id_number',
                            'username',
                            'email',
                            'image',
                            'image_id',
                            'name',
                            'address',
                            'phone_private',
                            'phone_work',
                            'liecese_validity_date',
                            'license_plate_no',
                            'availability',
                            'wallet',
                            'total_wallet',
                            'avg_delivery_time', //mins
                            'status',
                            'is_onilne',
                            'created_at',
                            'updated_at',
                        ]
                    ]
                ]
            );


    }


    public function testSetStatusDriver()
    {

        $driver = Driver::latest()->first();
        $response = $this->actingAs($this->admin, 'admin')->json('PUT', route('admin.personal.drivers.update-status', $driver->id), ['status' => 1], ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertJson(['status' => true]);
    }


    public function testLogsLoginDriver()
    {

        $driver = Driver::latest()->first();
        $response = $this->actingAs($this->admin, 'admin')->json('GET', route('admin.personal.drivers.logs-login', $driver->id), [], ['Accept' => 'application/json']);
        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(
                [
                    'status', 'success',
                    'data' => [],
                ]
            );
    }

    public function testLogsBreakDriver()
    {

        $driver = Driver::latest()->first();
        $response = $this->actingAs($this->admin, 'admin')->json('GET', route('admin.personal.drivers.logs-break', $driver->id), [], ['Accept' => 'application/json']);
        $response->assertStatus(200)
        ->assertJson(['success' => true])
        ->assertJsonStructure(
            [
                'status', 'success',
                'data' => [],
            ]
        );
    }

    public function testLogsOrdersDriver()
    {

        $driver = Driver::latest()->first();
        $response = $this->actingAs($this->admin, 'admin')->json('GET', route('admin.personal.drivers.orders', $driver->id), [], ['Accept' => 'application/json']);
        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(
                [
                    'status', 'success',
                    'data' => [],
                ]
            );
    }

    public function testLogsOrdersDispatchingDriver()
    {

        $driver = Driver::latest()->first();
        $response = $this->actingAs($this->admin, 'admin')->json('GET', route('admin.personal.drivers.orders-driver-dispatching', $driver->id), [], ['Accept' => 'application/json']);
        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(
                [
                    'status', 'success',
                    'data' => [],
                ]
            );
    }

    public function testDeleteDriver()
    {

        $driver = $this->driverCreate();
        $response = $this->actingAs($this->admin, 'admin')->json('DELETE', route('admin.personal.drivers.delete', $driver->id), [], ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertJson(['status' => true]);
    }

    private function driverCreate()
    {

        $data = [
            'area_id' => 1,
            'warehouse_id' => 1,
            'name' => "Driver Test",
            'username' => "DriverTest" . uniqid(rand(0, 100)),
            'email' => "DriverTest" . uniqid(rand(0, 100)) . "1121@gmailtest.com",
            'password' => '12341234',
            'address' => "Address of driver",
            'phone_private' => "01212010223",
            'phone_work' => "01212010223",
            'id_number' => rand(0, 100) . rand(0, 100) . rand(0, 100) . rand(0, 100),
            'liecese_validity_date' => "2020-11-11",
            'image' => $this->generateBase64Image(),
            'image_id' => $this->generateBase64Image(),
            'status' => 1,
        ];

        return $this->driverRepository->create($data);
    }

}
