<?php

namespace Tests\Unit\Portal\Admin;

use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\File;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Webkul\Admin\Repositories\User\UserRepository;
use Tests\TestCase;
use Webkul\User\Models\Admin;

class UserTest extends TestCase {

    public $admin;
    private $adminRepository;

    public function setUp(): void {
        parent::setUp();

        $this->adminRepository = resolve(UserRepository::class);
        $this->admin = $this->adminRepository->findOrFail(1);
        if (file_exists(storage_path('logs/unit-test.log'))) {
            unlink(storage_path('logs/unit-test.log'));
        }
    }

    public function testCreateAdminValidation() {
        $rules = [
            "areas",
            "roles",
            'name', 'email',
            'username',
            'password',
            'address',
            'phone_work', // this is mobile regex
            'phone_private', // this is mobile regex
            'image'];
        $data = [];

        $response = $this->actingAs($this->admin, 'admin')->postJson(
                route('admin.personal.users.store'),
                $data
        );

        $response->assertStatus(422)
                ->assertJsonValidationErrors($rules, 'data.errors');
    }

    public function testCreateAdmin() {

        $data = [
            "areas" => [
                1,
                2
            ],
            "warehouses" => [
                1,
                2
            ],
            "roles" => [
                3
            ],
            'name' => "Admin Test",
            'email' => "AdminTest" . uniqid(rand(0, 100)) . "1121@gmailtest.com",
            'username' => "AdminTest" . uniqid(rand(0, 100)),
            'id_number' => rand(0, 100) . rand(0, 100) . rand(0, 100) . rand(0, 100),
            'password' => bcrypt('12341234'),
            'address' => "Address of admin",
            'phone_work' => "01212010223",
            'phone_private' => "01212010223",
            'image' => $this->generateBase64Image(),
        ];

        $response = $this->actingAs($this->admin, 'admin')
                ->json('POST',
                route('admin.personal.users.store'),
                $data);

        $response->assertStatus(200)
                ->assertJson(['status' => 200])
                ->assertJson(['success' => true])
                ->assertJsonStructure(['status', 'success', 'message', 'data'
        ]);

        $this->assertDatabaseHas('admins', [
            'username' => $data['username'],
            'email' => $data['email']
        ]);
    }

    public function testUpdateAdminValidation() {
        $rules = [
            "areas",
            "roles",
            'name', 'email',
            'username',
            'address',
            'phone_work', // this is mobile regex
            'phone_private', // this is mobile regex
        ];
        $data = [];
        $driver = Admin::latest()->first();

        $this->actingAs($this->admin, 'admin')->putJson(
                        route('admin.personal.users.update', $driver->id),
                        $data
                )
                ->assertStatus(422)
                ->assertJsonValidationErrors($rules, 'data.errors');
    }

    public function testUpdateAdmin() {

        $admin = Admin::latest()->first();

        $data = [
            "areas" => [
                1,
                2
            ],
            "warehouses" => [
                1,
                2
            ],
            "roles" => [
                3
            ],
            'name' => "Admin Test",
            'email' => "AdminTest" . uniqid(rand(0, 100)) . "1121@gmailtest.com",
            'username' => "AdminTest" . uniqid(rand(0, 100)),
            'address' => "Address of admin",
            'phone_work' => "01212010223",
            'phone_private' => "01212010223",
        ];

        $response = $this->actingAs($this->admin, 'admin')->json('PUT', route('admin.personal.users.update', $admin->id), $data);

        $response->assertStatus(200)
                ->assertJson(['status' => 200])
                ->assertJson(['message' => 'Admin has been updated!'])
                ->assertJson(['success' => true])
                ->assertJsonStructure(['status', 'success', 'message', 'data'
        ]);
    }

    public function testShowAdmin() {

        $admin = Admin::latest()->first();

        $response = $this->actingAs($this->admin, 'admin')->json('GET', route('admin.personal.users.show', $admin->id), [], ['Accept' => 'application/json']);
        $response->assertStatus(200)
                ->assertJson(['success' => true])
                ->assertJsonStructure(
                        [
                            'status', 'success',
                            'data' => [
                                "id",
                                "name",
                                "email",
                                "username",
                                "id_number",
                                "is_verified",
                                "status",
                                "address",
                                "phone_work",
                                "phone_private",
                                "image",
                                "areas",
                                "warehouses",
                                "roles",
                                "permissions",
                                "created_at",
                                "updated_at"
                            ]
                        ]
        );
    }

    public function testAdminMe() {

        $admin = Admin::latest()->first();

        $response = $this->actingAs($this->admin, 'admin')->json('GET', route('admin.users.me', $admin->id), [], ['Accept' => 'application/json']);
        $response->assertStatus(200)
                ->assertJson(['success' => true])
                ->assertJsonStructure(
                        [
                            'status', 'success',
                            'data' => [
                                "id",
                                "name",
                                "email",
                                "username",
                                "id_number",
                                "is_verified",
                                "status",
                                "address",
                                "phone_work",
                                "phone_private",
                                "image",
                                "areas",
                                "warehouses",
                                "roles",
                                "permissions",
                                "created_at",
                                "updated_at"
                            ]
                        ]
        );
    }    
    public function testListAdmin() {

        $response = $this->actingAs($this->admin, 'admin')->json('GET', route('admin.personal.users.index'));
        $response->assertStatus(200)
                ->assertJson(['success' => true])
                ->assertJsonStructure(
                        [
                            'status', 'success',
                            'data' => [
                                0 => [
                                    "id",
                                    "name",
                                    "email",
                                    "username",
                                    "id_number",
                                    "is_verified",
                                    "status",
                                    "address",
                                    "phone_work",
                                    "phone_private",
                                    "image",
                                    "areas",
                                    "warehouses",
                                    "roles",
                                    "created_at",
                                    "updated_at"
                                ]
                            ]
                        ]
        );
    }

    public function testSetStatusAdmin() {

        $admin = Admin::latest()->first();
        $response = $this->actingAs($this->admin, 'admin')->json('PUT', route('admin.personal.users.update-status', $admin->id), ['status' => 1], ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertJson(['status' => true]);
    }

    public function testDeleteAdmin() {

        $admin = $this->adminCreate();
        $response = $this->actingAs($this->admin, 'admin')->json('DELETE', route('admin.personal.users.delete', $admin->id), [], ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertJson(['status' => true]);
    }

    private function adminCreate() {

        $data = [
            "areas" => [
                1,
                2
            ],
            "warehouses" => [
                1,
                2
            ],
            "roles" => [
                3
            ],
            'name' => "Admin Test",
            'email' => "AdminTest" . uniqid(rand(0, 100)) . "1121@gmailtest.com",
            'username' => "AdminTest" . uniqid(rand(0, 100)),
            'id_number' => rand(0, 100) . rand(0, 100) . rand(0, 100) . rand(0, 100),
            'password' => bcrypt('12341234'),
            'address' => "Address of admin",
            'phone_work' => "01212010223",
            'phone_private' => "01212010223",
            'image' => $this->generateBase64Image(),
        ];


        return $this->adminRepository->create($data);
    }

}
