<?php

namespace Tests\Unit\Portal\Banner;

use Tests\TestCase;
use Illuminate\Support\Str;
use Webkul\User\Models\Admin;
use Illuminate\Support\Facades\Event;
use Webkul\Core\Models\Shelve;
use Webkul\Banner\Models\Banner;

class BannerTest extends TestCase
{
    private $admin;

    public function setUp(): void
    {
        parent::setUp();
        $this->admin = Admin::find(1);
    }


    public function testCreateBannerValidation()
    {
        $data = [];

        $this->actingAs($this->admin, 'admin')->postJson(
            route('admin.app-management.banners.store'),
            $data
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'area_id', 'name', 'position', 'section', 'image_ar', 'image_en'
            ], 'data.errors');
    }

    public function testCreateSaleBanner()
    {
        $bannersCountBeforeCreate = Banner::count();
        $data = [
            'area_id'   =>  [1],
            'name'    =>  'Test Banner',
            'position'    =>  1,
            'status'    =>  1,
            'default'    =>  1,
            'section' =>  'sale',
            'image_ar' =>  $this->generateBase64Image(),
            'image_en' =>  $this->generateBase64Image(),
        ];

        $response = $this->actingAs($this->admin, 'admin')->postJson(route('admin.app-management.banners.store'), $data);
        $response->assertStatus(200)->assertJson(['status'    =>  200])->assertJsonStructure(['status', 'success', 'data']);

        $newBannerID = $response['data']['id'];

        $this->assertDatabaseCount('banners', $bannersCountBeforeCreate + 1);
        $this->assertDatabaseHas('banners', [
            'id'  =>  $newBannerID,
            'name'   =>  'Test Banner',
            'section'    =>  'sale',
        ]);
    }

    public function testCreateDealBanner()
    {
        $bannersCountBeforeCreate = Banner::count();
        $data = [
            'area_id'   =>  [1],
            'name'    =>  'Test Banner',
            'position'    =>  1,
            'status'    =>  1,
            'default'    =>  1,
            'section' =>  'deal',
            'image_ar' =>  $this->generateBase64Image(),
            'image_en' =>  $this->generateBase64Image(),
        ];

        $response = $this->actingAs($this->admin, 'admin')->postJson(route('admin.app-management.banners.store'), $data);
        $response->assertStatus(200)->assertJson(['status'    =>  200])->assertJsonStructure(['status', 'success', 'data']);

        $newBannerID = $response['data']['id'];

        $this->assertDatabaseCount('banners', $bannersCountBeforeCreate + 1);
        $this->assertDatabaseHas('banners', [
            'id'  =>  $newBannerID,
            'name'   =>  'Test Banner',
            'section'    =>  'deal',
        ]);
    }

    public function testUpdateBannerValidation()
    {
        $bannerID = Banner::latest()->first()->id;
        $data = [];

        $this->actingAs($this->admin, 'admin')->putJson(route('admin.app-management.banners.update', $bannerID), $data)
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'area_id', 'name', 'position', 'section'
            ], 'data.errors');
    }

    public function testUpdateBanner()
    {
        $banner = Banner::latest()->first();
        $bannerID = $banner->id;
        $data = [
            'area_id'   =>  1,
            'name'    =>  'Test Update Banner',
            'position'    =>  3,
            'section' =>  'deal'
        ];

        $this->actingAs($this->admin, 'admin')->putJson(route('admin.app-management.banners.update', $banner), $data)
            ->assertStatus(200)->assertJson(['status'    =>  200])->assertJsonStructure(['status', 'success', 'data']);

        $this->assertDatabaseHas('banners', [
            'id'  =>  $bannerID,
            'name' => 'Test Update Banner',
            'section' => 'deal',
        ]);
    }

    public function testUpdateBannerStatus()
    {
        $bannerID = Banner::latest()->first()->id;
        $data = [
            'status' => 0
        ];

        $this->actingAs($this->admin, 'admin')->putJson(
            route('admin.app-management.banners.update-status', $bannerID),
            $data
        )
            ->assertStatus(200)
            ->assertJson(['status'    =>  200])
            ->assertJsonStructure([
                'status', 'success', 'data'
            ]);

        $this->assertDatabaseHas('banners', [
            'id'  =>  $bannerID,
            'status' => 0,
        ]);
    }

    public function testGettingSaleBanners()
    {
        $this->actingAs($this->admin, 'admin')->getJson(route('admin.app-management.banners.index', ['section'  => 'deal']))
            ->assertStatus(200)
            ->assertJson(['success' =>  true])
            ->assertJsonStructure(
                [
                    'status', 'success', 'message',
                    'data'  =>  [
                        0   =>  [
                            'id',
                            'area',
                            'name',
                            'banner_type',
                            'action_id',
                            'section',
                            'start_date',
                            'end_date',
                            'position',
                            'status',
                            'default',
                            'image_en',
                            'image_ar',
                            'created_at',
                        ]
                    ]
                ]
            );
    }

    public function testShowBanner()
    {
        $banner = Banner::first();

        $this->actingAs($this->admin, 'admin')->getJson(
            route('admin.app-management.banners.show', $banner)
        )
            ->assertStatus(200)
            ->assertJson(['success' =>  true])
            ->assertJsonStructure([
                'status',
                'success',
                'data'  =>  [
                    'id',
                    'area_id',
                    'area',
                    'name',
                    'banner_type',
                    'action_id',
                    'actionObj',
                    'section',
                    'start_date',
                    'end_date',
                    'position',
                    'status',
                    'default',
                    'image_en',
                    'image_ar',
                    'created_at',
                ]
            ]);
    }


    public function testDeleteBanner()
    {
        $bannerID = Banner::latest()->first()->id;

        $this->actingAs($this->admin, 'admin')->deleteJson(route('admin.app-management.banners.delete', $bannerID))
            ->assertStatus(200)
            ->assertJson(['status'    =>  200])
            ->assertJsonStructure([
                'status', 'success', 'data'
            ]);

        $this->assertDatabaseMissing('banners', [
            'id'  =>  $bannerID,
        ]);
    }
}
