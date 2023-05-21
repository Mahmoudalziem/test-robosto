<?php

namespace Tests\Unit\Portal\Bundle;

use Tests\TestCase;
use Illuminate\Support\Str;
use Webkul\User\Models\Admin;
use Webkul\Bundle\Models\Bundle;

class BundleTest extends TestCase
{
    private $admin;

    public function setUp(): void
    {
        parent::setUp();
        $this->admin = Admin::find(1);
    }

    
    public function testCreateBundleValidation()
    {
        $data = [];

        $this->actingAs($this->admin, 'admin')->postJson(
            route('admin.app-management.promotions.bundles.store'),
            $data
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'image', 'discount_type', 'discount_value', 'areas', 'items', 'ar.name', 'ar.description', 'en.name', 'en.description'
            ], 'data.errors');
    }

    public function testCreateBundle()
    {
        $bundlesCountBeforeCreate = Bundle::count();
        $arName = "Arabic Name " . Str::random(4);
        $arDesc = "Arabic Description " . Str::random(10);
        $enName = "English Name " . Str::random(4);
        $enDesc = "English Description " . Str::random(10);

        $data = [
            'image'  =>  $this->generateBase64Image(),
            'discount_type' =>  'per',
            'discount_value' =>  10,
            'start_validity'    =>  now()->format('Y-m-d h:i:s'),
            'end_validity'    =>  now()->addDays(10)->format('Y-m-d h:i:s'),
            'areas'     =>  [1],
            'items' =>  [
                ['id'   =>  1, 'qty'    =>  5],
                ['id'   =>  2, 'qty'    =>  3],
            ],
            'ar' => [
                'name'  =>  $arName,
                'description'  =>  $arDesc,
            ],
            'en'    =>  [
                'name'  =>  $enName,
                'description'  =>  $enDesc,
            ],
        ];

        $response = $this->actingAs($this->admin, 'admin')->postJson(
            route('admin.app-management.promotions.bundles.store'),
            $data
        );
        $response
            ->assertStatus(200)
            ->assertJson(['status'    =>  200])
            ->assertJsonStructure([
                'status', 'success', 'data'
            ]);

        $newBundleID = $response['data']['id'];

        $this->assertDatabaseCount('bundles', $bundlesCountBeforeCreate + 1);
        $this->assertDatabaseHas('bundles', [
            'id'  =>  $newBundleID,
            'discount_type'   =>  'per',
            'discount_value'    =>  10,
        ]);
        $this->assertDatabaseHas('bundle_items', [
            'bundle_id'  =>  $newBundleID,
            'product_id'   =>  1,
            'quantity'    =>  5,
        ]);
        $this->assertDatabaseHas('bundle_translations', [
            'locale'  =>  'ar',
            'name' => $arName,
            'description' => $arDesc,
        ]);

        $this->assertDatabaseHas('bundle_translations', [
            'locale'  =>  'en',
            'name' => $enName,
            'description' => $enDesc,
        ]);
    }

    public function testUpdateBundleValidation()
    {
        $bundleID = Bundle::latest()->first()->id;
        $data = [];

        $this->actingAs($this->admin, 'admin')->postJson(
            route('admin.app-management.promotions.bundles.update', $bundleID),
            $data
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'discount_type', 'discount_value', 'area_id', 'items', 'ar.name', 'ar.description', 'en.name', 'en.description'
            ], 'data.errors');
    }

    public function testUpdateBundle()
    {
        $bundleID = Bundle::latest()->first()->id;
        $arName = "Arabic Name Updated";
        $arDesc = "Arabic Description " . Str::random(10);
        $enName = "English Name Updated";
        $enDesc = "English Description " . Str::random(10);

        $data = [
            'discount_type' =>  'per',
            'discount_value' =>  12,
            'start_validity'    =>  now()->format('Y-m-d h:i:s'),
            'end_validity'    =>  now()->addDays(10)->format('Y-m-d h:i:s'),
            'area_id'     =>  1,
            'items' =>  [
                ['id'   =>  1, 'qty'    =>  4],
                ['id'   =>  2, 'qty'    =>  3],
            ],
            'ar' => [
                'name'  =>  $arName,
                'description'  =>  $arDesc,
            ],
            'en'    =>  [
                'name'  =>  $enName,
                'description'  =>  $enDesc,
            ],
        ];

        $response =  $this->actingAs($this->admin, 'admin')->postJson(
            route('admin.app-management.promotions.bundles.update', $bundleID),
            $data
        );

        $response
            ->assertStatus(200)
            ->assertJson(['status'    =>  200])
            ->assertJsonStructure([
                'status', 'success', 'data'
            ]);

        $this->assertDatabaseHas('bundles', [
            'id'  =>  $bundleID,
            'discount_value' => 12,
        ]);
        $this->assertDatabaseHas('bundle_items', [
            'bundle_id'  =>  $bundleID,
            'product_id'   =>  1,
            'quantity'    =>  4,
        ]);

        $this->assertDatabaseHas('bundle_translations', [
            'bundle_id'   =>  $bundleID,
            'locale'  =>  'ar',
            'name' => $arName,
        ]);

        $this->assertDatabaseHas('bundle_translations', [
            'bundle_id'   =>  $bundleID,
            'locale'  =>  'en',
            'name' => $enName,
        ]);
    }

    public function testGettingBundles()
    {
        $this->actingAs($this->admin, 'admin')->getJson(route('admin.app-management.promotions.bundles.index'))
        ->assertStatus(200)
            ->assertJson(['success' =>  true])
            ->assertJsonStructure(
                [
                    'status', 'success', 'message',
                    'data'  =>  [
                        0   =>  [
                            'id',
                            'discount_type',
                            'discount_value',
                            'image_url',
                            'thumb_url',
                            'amount',
                            'status',
                            'start_validity',
                            'end_validity',
                            'total_original_price',
                            'area_id',
                            'area_name',
                            'items',
                            'created_at',
                            'updated_at',
                        ]
                    ]
                ]
            );
    }

    public function testShowBundle()
    {
        $bundle = Bundle::first();

        $this->actingAs($this->admin, 'admin')->getJson(
            route('admin.app-management.promotions.bundles.show', $bundle)
        )
            ->assertStatus(200)
            ->assertJson(['success' =>  true])
            ->assertJsonStructure([
                'status',
                'success',
                'data'  =>  [
                    'id',
                    'discount_type',
                    'discount_value',
                    'image_url',
                    'thumb_url',
                    'amount',
                    'status',
                    'start_validity',
                    'end_validity',
                    'total_original_price',
                    'area_id',
                    'area_name',
                    'items',
                    'created_at',
                    'updated_at',
                ]
            ]);
    }


    public function testDeleteBundle()
    {
        $bundleID = Bundle::latest()->first()->id;

        $this->actingAs($this->admin, 'admin')->postJson(route('admin.app-management.promotions.bundles.delete', $bundleID))
            ->assertStatus(200)
            ->assertJson(['status'    =>  200])
            ->assertJsonStructure([
                'status', 'success', 'data'
            ]);

        $this->assertDatabaseMissing('bundles', [
            'id'  =>  $bundleID,
        ]);
    }
}
