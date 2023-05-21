<?php

namespace Tests\Unit\Portal\Category;

use Tests\TestCase;
use Illuminate\Support\Str;
use Webkul\User\Models\Admin;
use Webkul\Category\Models\Category;
use Illuminate\Support\Facades\Event;
use Webkul\Category\Models\SubCategory;

class CategoryTest extends TestCase
{
    private $admin;

    public function setUp(): void
    {
        parent::setUp();
        $this->admin = Admin::find(1);
    }

    public function testGettingCategories()
    {

        $this->actingAs($this->admin, 'admin')->getJson(route('admin.app-management.categories.index'))
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
                            'image_url',
                            'status',
                            'created_at',
                            'updated_at',
                        ]
                    ]
                ]
            );
    }

    public function testShowCategory()
    {
        $category = Category::first();

        $this->actingAs($this->admin, 'admin')->getJson(
            route('admin.app-management.categories.show', $category)
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
                    'image',
                    'image_url',
                    'status',
                    'sold_count',
                    'sub_categories',
                    'translations',
                    'created_at',
                    'updated_at',
                ]
            ]);
    }

    public function testShowSubCategoriesOfCategory()
    {
        $category = Category::first();

        $this->actingAs($this->admin, 'admin')->getJson(
            route('admin.app-management.categories.subcategories-list', $category)
        )
            ->assertStatus(200)
            ->assertJson(['success' =>  true])
            ->assertJsonStructure([
                'status',
                'success',
                'data'  =>
                [
                    0 => [
                        'id',
                        'sold_count',
                        'image',
                        'image_url',
                        'thumb',
                        'thumb_url',
                        'status',
                        'position',
                        'name',
                        'translations',
                        'parent_categories',
                        'created_at',
                        'updated_at',
                    ]
                ]
            ]);
    }

    public function testShowProductsOfSubCategory()
    {
        $subCategory = SubCategory::first();

        $this->actingAs($this->admin, 'admin')->getJson(
            route('admin.app-management.categories.subcategories-products-list', $subCategory)
        )
            ->assertStatus(200)
            ->assertJson(['success' =>  true])
            ->assertJsonStructure([
                'status',
                'success',
                'data'  =>
                [
                    0 => [
                        "id",
                        "barcode",
                        "prefix",
                        "image",
                        "thumb",
                        "featured",
                        "status",
                        "returnable",
                        "price",
                        "cost",
                        "tax",
                        "weight",
                        "width",
                        "height",
                        "length",
                        "sold_count",
                        "visits_count",
                        "shelve_id",
                        "brand_id",
                        "unit_id",
                        "unit_value",
                        "created_at",
                        "updated_at",
                        "image_url",
                        "thumb_url",
                        "total_in_stock",
                        "name",
                        "description",
                        "translations",
                    ]
                ]
            ]);
    }

    public function testCreateCategoryValidation()
    {
        $data = [];

        $this->actingAs($this->admin, 'admin')->postJson(
            route('admin.app-management.categories.store'),
            $data
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'image', 'ar.name', 'en.name'
            ], 'data.errors');
    }

    public function testCreateCategory()
    {
        $categoriesCountBeforeCreate = Category::count();
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
            route('admin.app-management.categories.store'),
            $data
        );

        $response
            ->assertStatus(200)
            ->assertJson(['status'    =>  200])
            ->assertJsonStructure([
                'status', 'success', 'data'
            ]);

        $this->assertDatabaseCount('categories', $categoriesCountBeforeCreate + 1);
        $this->assertDatabaseHas('category_translations', [
            'locale'  =>  'ar',
            'name' => $arName,
        ]);

        $this->assertDatabaseHas('category_translations', [
            'locale'  =>  'en',
            'name' => $enName,
        ]);
    }

    public function testUpdateCategoryValidation()
    {
        $categoryID = Category::latest()->first()->id;
        $data = [];

        $this->actingAs($this->admin, 'admin')->postJson(
            route('admin.app-management.categories.update', $categoryID),
            $data
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'ar.name', 'en.name'
            ], 'data.errors');
    }

    public function testUpdateCategory()
    {
        $categoryID = Category::latest()->first()->id;
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
            route('admin.app-management.categories.update', $categoryID),
            $data
        )
            ->assertStatus(200)
            ->assertJson(['status'    =>  200])
            ->assertJsonStructure([
                'status', 'success', 'data'
            ]);

        $this->assertDatabaseHas('categories', [
            'id'  =>  $categoryID,
            'position' => 8,
        ]);

        $this->assertDatabaseHas('category_translations', [
            'category_id'   =>  $categoryID,
            'locale'  =>  'ar',
            'name' => $arName,
        ]);

        $this->assertDatabaseHas('category_translations', [
            'category_id'   =>  $categoryID,
            'locale'  =>  'en',
            'name' => $enName,
        ]);
    }

    public function testUpdateCategoryStatus()
    {
        $categoryID = Category::latest()->first()->id;
        $data = [
            'status' => 1
        ];


        $this->actingAs($this->admin, 'admin')->postJson(
            route('admin.app-management.categories.update-status', $categoryID),
            $data
        )
            ->assertStatus(200)
            ->assertJson(['status'    =>  200])
            ->assertJsonStructure([
                'status', 'success', 'data'
            ]);

        $this->assertDatabaseHas('categories', [
            'id'  =>  $categoryID,
            'status' => 1,
        ]);
    }


    public function testDeleteCategory()
    {
        $categoryID = Category::latest()->first()->id;

        $this->actingAs($this->admin, 'admin')->postJson(route('admin.app-management.categories.delete', $categoryID))
            ->assertStatus(200)
            ->assertJson(['status'    =>  200])
            ->assertJsonStructure([
                'status', 'success', 'data'
            ]);

        $this->assertDatabaseMissing('categories', [
            'id'  =>  $categoryID,
        ]);
    }
}
