<?php

namespace Tests\Unit\Portal\SubCategory;

use Tests\TestCase;
use Illuminate\Support\Str;
use Webkul\User\Models\Admin;
use Webkul\Category\Models\Category;
use Illuminate\Support\Facades\Event;
use Webkul\Category\Models\SubCategory;

class SubCategoryTest extends TestCase
{
    private $admin;

    public function setUp(): void
    {
        parent::setUp();
        $this->admin = Admin::find(1);
    }

    public function testGettingSubCategories()
    {

        $this->actingAs($this->admin, 'admin')->getJson(route('admin.app-management.sub-categories.index'))
            ->assertStatus(200)
            ->assertJson(['success' =>  true])
            ->assertJsonStructure(
                [
                    'status', 'success', 'message',
                    'data'  =>  [
                        0   =>  [
                            'id',
                            'name',
                            'position',
                            'image',
                            'image_url',
                            'status',
                            'parent_categories',
                            'created_at',
                            'updated_at',
                        ]
                    ]
                ]
            );
    }

    public function testShowSubCategory()
    {
        $category = Category::first();

        $this->actingAs($this->admin, 'admin')->getJson(
            route('admin.app-management.sub-categories.show', $category)
        )
            ->assertStatus(200)
            ->assertJson(['success' =>  true])
            ->assertJsonStructure([
                'status',
                'success',
                'data'  =>  [
                    'id',
                    'name',
                    'position',
                    'sold_count',
                    'image',
                    'image_url',
                    'status',
                    'parent_categories',
                    'created_at',
                    'updated_at',
                ]
            ]);
    }

    public function testCreateSubCategoryValidation()
    {
        $data = [];

        $this->actingAs($this->admin, 'admin')->postJson(
            route('admin.app-management.sub-categories.store'),
            $data
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'image', 'ar.name', 'en.name'
            ], 'data.errors');
    }

    public function testCreateSubCategory()
    {
        $subCategoriesCountBeforeCreate = SubCategory::count();
        $arName = "Arabic Name" . Str::random(4);
        $enName = "English Name" . Str::random(4);

        $data = [
            'ar' => [
                'name'  =>  $arName
            ],
            'en'    =>  [
                'name'  =>  $enName
            ],
            'position'  =>  18,
            'image' =>  $this->generateBase64Image(),
        ];

        $response = $this->actingAs($this->admin, 'admin')->postJson(
            route('admin.app-management.sub-categories.store'),
            $data
        );

        $response
            ->assertStatus(200)
            ->assertJson(['status'    =>  200])
            ->assertJsonStructure([
                'status', 'success', 'data'
            ]);

        $this->assertDatabaseCount('sub_categories', $subCategoriesCountBeforeCreate + 1);
        $this->assertDatabaseHas('sub_category_translations', [
            'locale'  =>  'ar',
            'name' => $arName,
        ]);

        $this->assertDatabaseHas('sub_category_translations', [
            'locale'  =>  'en',
            'name' => $enName,
        ]);
    }

    public function testUpdateSubCategoryValidation()
    {
        $subCategoryID = SubCategory::latest()->first()->id;
        $data = [];

        $this->actingAs($this->admin, 'admin')->postJson(
            route('admin.app-management.sub-categories.update', $subCategoryID),
            $data
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'ar.name', 'en.name'
            ], 'data.errors');
    }

    public function testUpdateSubCategory()
    {
        $subCategoryID = SubCategory::latest()->first()->id;
        $arName = "Arabic Name" . Str::random(4);
        $enName = "English Name" . Str::random(4);
        $data = [
            'ar' => [
                'name'  =>  $arName
            ],
            'en'    =>  [
                'name'  =>  $enName
            ],
            'position'  =>  8,
            'image' =>  $this->generateBase64Image(),
        ];


        $this->actingAs($this->admin, 'admin')->postJson(
            route('admin.app-management.sub-categories.update', $subCategoryID),
            $data
        )
            ->assertStatus(200)
            ->assertJson(['status'    =>  200])
            ->assertJsonStructure([
                'status', 'success', 'data'
            ]);

        $this->assertDatabaseHas('sub_categories', [
            'id'  =>  $subCategoryID,
            'position' => 8,
        ]);

        $this->assertDatabaseHas('sub_category_translations', [
            'sub_category_id'   =>  $subCategoryID,
            'locale'  =>  'ar',
            'name' => $arName,
        ]);

        $this->assertDatabaseHas('sub_category_translations', [
            'sub_category_id'   =>  $subCategoryID,
            'locale'  =>  'en',
            'name' => $enName,
        ]);
    }

    public function testUpdateCategoryStatus()
    {
        $subCategoryID = SubCategory::latest()->first()->id;
        $data = [
            'status' => 1
        ];


        $this->actingAs($this->admin, 'admin')->postJson(
            route('admin.app-management.sub-categories.update-status', $subCategoryID),
            $data
        )
            ->assertStatus(200)
            ->assertJson(['status'    =>  200])
            ->assertJsonStructure([
                'status', 'success', 'data'
            ]);

        $this->assertDatabaseHas('sub_categories', [
            'id'  =>  $subCategoryID,
            'status' => 1,
        ]);
    }


    public function testDeleteCategory()
    {
        $subCategoryID = SubCategory::latest()->first()->id;

        $this->actingAs($this->admin, 'admin')->postJson(route('admin.sub-categories.delete', $subCategoryID))
            ->assertStatus(200)
            ->assertJson(['status'    =>  200])
            ->assertJsonStructure([
                'status', 'success', 'data'
            ]);

        $this->assertDatabaseMissing('sub_categories', [
            'id'  =>  $subCategoryID,
        ]);
    }
}
