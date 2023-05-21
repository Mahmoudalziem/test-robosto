<?php

namespace Tests\Unit\Portal\Promotion;

use Tests\TestCase;
use Illuminate\Support\Str;
use Webkul\User\Models\Admin;
use Illuminate\Support\Facades\Event;
use Webkul\Promotion\Models\Promotion;

class PromotionTest extends TestCase
{
    private $admin;

    public function setUp(): void
    {
        parent::setUp();
        $this->admin = Admin::find(1);
    }

    public function testCreatePromotionValidation()
    {
        $data = [];

        $this->actingAs($this->admin, 'admin')->postJson(
            route('admin.app-management.promotions.promocodes.store'),
            $data
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'areas', 'tags', 'promo_code', 'discount_type', 'discount_value', 'total_vouchers', 'total_redeems_allowed', 'price_applied', 'apply_type',
                'apply_content', 'ar.title', 'ar.description', 'en.title', 'en.description'
            ], 'data.errors');
    }

    public function testCreatePromotion()
    {
        $promotionsCountBeforeCreate = Promotion::count();
        $arTitle = "Arabic Title " . Str::random(4);
        $arDesc = "Arabic Description " . Str::random(10);
        $enTitle = "English Title " . Str::random(4);
        $enDesc = "English Description " . Str::random(10);
        $data = [
            'areas'   =>  [1],
            'tags'    =>  [1, 2],
            'promo_code' =>  'test-promo',
            'discount_type'    =>  'val',
            'discount_value'    =>  10,
            'total_vouchers' =>  20,
            'total_redeems_allowed'    =>  3,
            'price_applied'    =>  'original',
            'apply_type'    =>  'product',
            'apply_content'    =>  [1, 2],
            'ar' => [
                'title'  =>  $arTitle,
                'description'  =>  $arDesc,
            ],
            'en'    =>  [
                'title'  =>  $enTitle,
                'description'  =>  $enDesc,
            ],
        ];

        $response = $this->actingAs($this->admin, 'admin')->postJson(
            route('admin.app-management.promotions.promocodes.store'),
            $data
        );
        $response
            ->assertStatus(200)
            ->assertJson(['status'    =>  200])
            ->assertJsonStructure([
                'status', 'success', 'data'
            ]);

        $newPromotionID = $response['data']['id'];

        $this->assertDatabaseCount('promotions', $promotionsCountBeforeCreate + 1);
        $this->assertDatabaseHas('promotions', [
            'id'  =>  $newPromotionID,
            'promo_code'   =>  'test-promo'
        ]);
        $this->assertDatabaseHas('promotion_translations', [
            'locale'  =>  'ar',
            'title' => $arTitle,
            'description' => $arDesc,
        ]);

        $this->assertDatabaseHas('promotion_translations', [
            'locale'  =>  'en',
            'title' => $enTitle,
            'description' => $enDesc,
        ]);

        $this->assertDatabaseHas('promotion_applies', [
            'promotion_id'  =>  $newPromotionID,
            'apply_type'   =>  'product',
        ]);
        $this->assertDatabaseHas('promotion_areas', [
            'promotion_id'  =>  $newPromotionID,
            'area_id'   =>  1,
        ]);
        $this->assertDatabaseHas('promotion_tags', [
            'promotion_id'  =>  $newPromotionID,
            'tag_id'   =>  1,
        ]);
        
        $this->assertDatabaseHas('promotion_products', [
            'promotion_id'  =>  $newPromotionID,
            'product_id'   =>  1,
        ]);
    }

    public function testUpdatePromotionValidation()
    {
        $promotionID = Promotion::latest()->first()->id;
        $data = [];

        $this->actingAs($this->admin, 'admin')->putJson(
            route('admin.app-management.promotions.promocodes.update', $promotionID),
            $data
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'areas', 'tags', 'promo_code', 'discount_type', 'discount_value', 'total_vouchers', 'total_redeems_allowed', 'price_applied', 'apply_type',
                'apply_content', 'ar.title', 'ar.description', 'en.title', 'en.description'
            ], 'data.errors');
    }

    public function testUpdatePromotion()
    {
        $promotion = Promotion::latest()->first();
        $promotionID = $promotion->id;

        $arTitle = "Arabic Title Updated";
        $arDesc = "Arabic Description " . Str::random(10);
        $enTitle = "English Title Updated";
        $enDesc = "English Description " . Str::random(10);

        $data = [
            'areas'   =>  [1],
            'tags'    =>  [1, 3],
            'promo_code' =>  'test-update-promo',
            'discount_type'    =>  'val',
            'discount_value'    =>  15,
            'total_vouchers' =>  25,
            'total_redeems_allowed'    =>  5,
            'price_applied'    =>  'original',
            'apply_type'    =>  'product',
            'apply_content'    =>  [1, 2, 3],
            'ar' => [
                'title'  =>  $arTitle,
                'description'  =>  $arDesc,
            ],
            'en'    =>  [
                'title'  =>  $enTitle,
                'description'  =>  $enDesc,
            ],
        ];

        $response =  $this->actingAs($this->admin, 'admin')->putJson(
            route('admin.app-management.promotions.promocodes.update', $promotionID),
            $data
        );

        $response
            ->assertStatus(200)
            ->assertJson(['status'    =>  200])
            ->assertJsonStructure([
                'status', 'success', 'data'
            ]);

        $this->assertDatabaseHas('promotions', [
            'id'  =>  $promotionID,
            'promo_code'   =>  'test-update-promo',
            'discount_value'   =>  15,
            'total_vouchers'   =>  25,

        ]);
        $this->assertDatabaseHas('promotion_translations', [
            'locale'  =>  'ar',
            'title' => $arTitle,
            'description' => $arDesc,
        ]);

        $this->assertDatabaseHas('promotion_translations', [
            'locale'  =>  'en',
            'title' => $enTitle,
            'description' => $enDesc,
        ]);

        $this->assertDatabaseHas('promotion_applies', [
            'promotion_id'  =>  $promotionID,
            'apply_type'   =>  'product',
        ]);
        $this->assertDatabaseHas('promotion_areas', [
            'promotion_id'  =>  $promotionID,
            'area_id'   =>  1,
        ]);
        $this->assertDatabaseHas('promotion_tags', [
            'promotion_id'  =>  $promotionID,
            'tag_id'   =>  3,
        ]);
        $this->assertDatabaseHas('promotion_products', [
            'promotion_id'  =>  $promotionID,
            'product_id'   =>  3,
        ]);
        
    }

    public function testUpdatePromotionStatus()
    {
        $promotionID = Promotion::latest()->first()->id;
        $data = [
            'status' => 0
        ];

        $this->actingAs($this->admin, 'admin')->putJson(
            route('admin.app-management.promotions.promocodes.update-status', $promotionID),
            $data
        )
            ->assertStatus(200)
            ->assertJson(['status'    =>  200])
            ->assertJsonStructure([
                'status', 'success', 'data'
            ]);

        $this->assertDatabaseHas('promotions', [
            'id'  =>  $promotionID,
            'status' => 0,
        ]);
    }

    public function testGettingPromotions()
    {
        $this->actingAs($this->admin, 'admin')->getJson(route('admin.app-management.promotions.promocodes.index'))
        ->assertStatus(200)
            ->assertJson(['success' =>  true])
            ->assertJsonStructure(
                [
                    'status', 'success', 'message',
                    'data'  =>  [
                        0   =>  [
                            'id',
                            'areas',
                            'tags',
                            'title',
                            'promo_code',
                            'description',
                            'discount_type',
                            'discount_value',
                            'start_validity',
                            'end_validity',
                            'promo_validity',
                            'total_vouchers',
                            'usage_vouchers',
                            'minimum_order_amount',
                            'minimum_items_quantity',
                            'total_redeems_allowed',
                            'price_applied',
                            'apply_type',
                            'exceptions_items',
                            'send_notifications',
                            'is_valid',
                            'status',
                            'created_at',
                        ]
                    ]
                ]
            );
    }


    public function testShowPromotion()
    {
        $promotion = Promotion::first();

        $this->actingAs($this->admin, 'admin')->getJson(
            route('admin.app-management.promotions.promocodes.show', $promotion)
        )
            ->assertStatus(200)
            ->assertJson(['success' =>  true])
            ->assertJsonStructure([
                'status',
                'success',
                'data'  =>  [
                    'id',
                    'areas',
                    'tags',
                    'ar',
                    'en',
                    'promo_code',
                    'discount_type',
                    'discount_value',
                    'start_validity',
                    'end_validity',
                    'promo_validity',
                    'total_vouchers',
                    'usage_vouchers',
                    'minimum_order_amount',
                    'minimum_items_quantity',
                    'total_redeems_allowed',
                    'price_applied',
                    'apply_type',
                    'apply_content',
                    'exceptions_items',
                    'send_notifications',
                    'is_valid',
                    'status',
                    'created_at'
                ]
            ]);
    }
}
