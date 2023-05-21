<?php

namespace Tests\Unit\Portal\Discount;

use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\File;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Webkul\Admin\Repositories\Discount\DiscountRepository;
use Webkul\Admin\Repositories\User\UserRepository;
use Webkul\Discount\Models\Discount;
use Tests\TestCase;

class DiscountTest extends TestCase {

    public $admin;
    private $adminRepository;
    private $discountRepository;

    public function setUp(): void {
        parent::setUp();

        $this->adminRepository = resolve(UserRepository::class);
        $this->discountRepository = resolve(DiscountRepository::class);
        $this->admin = $this->adminRepository->findOrFail(1);
        if (file_exists(storage_path('logs/unit-test.log'))) {
            unlink(storage_path('logs/unit-test.log'));
        }
    }

    public function testCreateAdminValidation() {
        $rules = [
            "area_id",
            'product_id',
            'discount_type',
            'discount_value',
        ];
        $data = [];

        $response = $this->actingAs($this->admin, 'admin')->postJson(
                route('admin.app-management.promotions.discounts.store'),
                $data
        );

        $response->assertStatus(422)
                ->assertJsonValidationErrors($rules, 'data.errors');
    }

    public function testCreateDiscount() {

        $data = [
            "area_id" => 1,
            "product_id" => 1,
            "discount_type" => 'per',
            'discount_value' => 10,
            'discount_qty' => 15,
            'start_validity' => now()->format('Y-m-d h:i:s'),
            'end_validity' => now()->format('Y-m-d h:i:s'),
            'status' => 1,
        ];

        $response = $this->actingAs($this->admin, 'admin')
                ->json('POST',
                route('admin.app-management.promotions.discounts.store'),
                $data);

        $response->assertStatus(200)
                ->assertJson(['status' => 200])
                ->assertJson(['success' => true])
                ->assertJsonStructure(['status', 'success', 'message', 'data'
        ]);

        $this->assertDatabaseHas('discounts', [
            'area_id' => $data['area_id'],
            'product_id' => $data['product_id']
        ]);
    }

    public function testUpdateDiscountValidation() {
        $rules = [
            "area_id",
            'product_id',
            'discount_type',
            'discount_value'
        ];
        $data = [];
        $driver = Discount::latest()->first();

        $this->actingAs($this->admin, 'admin')->putJson(
                        route('admin.app-management.promotions.discounts.update', $driver->id),
                        $data
                )
                ->assertStatus(422)
                ->assertJsonValidationErrors($rules, 'data.errors');
    }

    public function testUpdateDiscount() {

        $discount = Discount::latest()->first();

        $data = [
            "area_id" => 1,
            "product_id" => 1,
            "discount_type" => 'per',
            'discount_value' => 10,
            'discount_qty' => 15,
            'start_validity' => now()->format('Y-m-d h:i:s'),
            'end_validity' => now()->format('Y-m-d h:i:s'),
            'status' => 1,
        ];

        $response = $this->actingAs($this->admin, 'admin')->json('PUT', route('admin.app-management.promotions.discounts.update', $discount->id), $data);

        $response->assertStatus(200)
                ->assertJson(['status' => 200])
                ->assertJson(['success' => true])
                ->assertJsonStructure(['status', 'success', 'message', 'data'
        ]);
    }

    public function testShowDiscount() {

        $discount = Discount::latest()->first();

        $response = $this->actingAs($this->admin, 'admin')->json('GET', route('admin.app-management.promotions.discounts.show', $discount->id), [], ['Accept' => 'application/json']);
        $response->assertStatus(200)
                ->assertJson(['success' => true])
                ->assertJsonStructure(
                        [
                            'status', 'success',
                            'data' => [
                                "id",
                                "discount_type",
                                "discount_value",
                                'discount_qty',
                                "area_id",
                                "product_id",
                                "orginal_price",
                                "discount_price",
                                "start_validity",
                                "end_validity",
                                "created_at",
                                "updated_at",
                            ]
                        ]
        );
    }

    public function testListDiscount() {

        $response = $this->actingAs($this->admin, 'admin')->json('GET', route('admin.app-management.promotions.discounts.index'));
        $response->assertStatus(200)
                ->assertJson(['success' => true])
                ->assertJsonStructure(
                        [
                            'status', 'success',
                            'data' => [
                                0 => [
                                    "id",
                                    "discount_type",
                                    "discount_value",
                                    "discount_qty",
                                    "area_id",
                                    "product_id",
                                    "orginal_price",
                                    "discount_price",
                                    "start_validity",
                                    "end_validity",
                                    "created_at",
                                    "updated_at"
                                ]
                            ]
                        ]
        );
    }

}
