<?php

namespace Tests\Unit\Portal\Product;

use Tests\TestCase;
use Illuminate\Support\Str;
use Webkul\User\Models\Admin;
use Illuminate\Support\Facades\Event;
use Webkul\Product\Models\ProductTag;

class ProductTagTest extends TestCase {

    private $admin;

    public function setUp(): void {
        parent::setUp();
        $this->admin = Admin::find(1);
    }

    public function testCreateProductTagValidation() {
        $data = [];

        $this->actingAs($this->admin, 'admin')->postJson(
                        route('admin.app-management.producttags.store'),
                        $data
                )
                ->assertStatus(422)
                ->assertJsonValidationErrors([
                    'ar.name', 'en.name',], 'data.errors');
    }

    public function testCreateProduct() {
        $producttagsCountBeforeCreate = ProductTag::count();
        $arName = "Arabic Name " . Str::random(4);
        $enName = "English Name " . Str::random(4);
        $status = 1;

        $data = [
            'status' => $status,
            'ar' => [
                'name' => $arName,
            ],
            'en' => [
                'name' => $enName,
            ],
        ];

        $response = $this->actingAs($this->admin, 'admin')->postJson(
                route('admin.app-management.producttags.store'),
                $data
        );
        $response
                ->assertStatus(200)
                ->assertJson(['status' => 200])
                ->assertJsonStructure([
                    'status', 'success', 'data'
        ]);

        $newProductTagID = $response['data']['id'];

        $this->assertDatabaseCount('producttags', $producttagsCountBeforeCreate + 1);
        $this->assertDatabaseHas('producttags', [
            'id' => $newProductTagID,
            'status' => $status,
        ]);

        $this->assertDatabaseHas('producttag_translations', [
            'locale' => 'ar',
            'name' => $arName,
        ]);

        $this->assertDatabaseHas('producttag_translations', [
            'locale' => 'en',
            'name' => $enName,
        ]);
    }

    public function testUpdateProductTagValidation() {
        $producttagID = ProductTag::latest()->first()->id;
        $data = [];

        $this->actingAs($this->admin, 'admin')->putJson(
                        route('admin.app-management.producttags.update', $producttagID),
                        $data
                )
                ->assertStatus(422)
                ->assertJsonValidationErrors(['ar.name', 'en.name'], 'data.errors');
    }

    public function testUpdateProduct() {
        $product = ProductTag::latest()->first();
        $producttagID = $product->id;

        $arName = "Arabic Name Updated";
        $enName = "English Name Updated";
        $status = 1;

        $data = [
            'status' => $status,
            'ar' => [
                'name' => $arName,
            ],
            'en' => [
                'name' => $enName,
            ],
        ];

        $response = $this->actingAs($this->admin, 'admin')->putJson(
                route('admin.app-management.producttags.update', $producttagID),
                $data
        );

        $response
                ->assertStatus(200)
                ->assertJson(['status' => 200])
                ->assertJsonStructure([
                    'status', 'success', 'data'
        ]);
    }

    public function testGettingProductTags() {

        $this->actingAs($this->admin, 'admin')->getJson(route('admin.app-management.producttags.index'))
                ->assertStatus(200)
                ->assertJson(['success' => true])
                ->assertJsonStructure(
                        [
                            'status', 'success', 'message',
                            'data' => [
                                0 => [
                                    'id',
                                    'name',
                                    'status',
                                    'created_at',
                                    'updated_at',
                                ]
                            ]
                        ]
        );
    }

    public function testShowProductTag() {
        $producttag = ProductTag::first();

        $this->actingAs($this->admin, 'admin')->getJson(
                        route('admin.app-management.producttags.show', $producttag)
                )
                ->assertStatus(200)
                ->assertJson(['success' => true])
                ->assertJsonStructure([
                    'status',
                    'success',
                    'data' => [
                        'id',
                        'name',
                        'status',
                        'created_at',
                        'updated_at',
                    ]
        ]);
    }

}
