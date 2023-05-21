<?php

namespace Tests\Unit\Portal\Core;

use Tests\TestCase;
use Illuminate\Support\Str;
use Webkul\User\Models\Admin;
use Illuminate\Support\Facades\Event;

class CoreTest extends TestCase
{
    private $admin;

    public function setUp(): void
    {
        parent::setUp();
        $this->admin = Admin::find(1);
    }

    public function testGettingChannels()
    {
        $this->actingAs($this->admin, 'admin')->getJson(route('admin.core.channel.list'))
            ->assertStatus(200)
            ->assertJsonStructure(
                [
                    'status', 'success',
                    'data'  =>  [
                        0   =>  [
                            'id',
                            'name',
                            'created_at',
                            'updated_at',
                        ]
                    ]
                ]
            );
    }

    public function testGettingAreas()
    {
        $this->actingAs($this->admin, 'admin')->getJson(route('admin.core.area.list'))
            ->assertStatus(200)
            ->assertJsonStructure(
                [
                    'status', 'success',
                    'data'  =>  [
                        0   =>  [
                            'id',
                            'name',
                            'default',
                            'status',
                            'created_at',
                            'updated_at',
                        ]
                    ]
                ]
            );
    }

    public function testFetchAllProducts()
    {
        $this->actingAs($this->admin, 'admin')->getJson(route('admin.core.fetchAll', 'products'))
            ->assertStatus(200)
            ->assertJsonStructure(
                [
                    'status', 'success',
                    'data'  =>  [
                        0   =>  [
                            'id',
                            'name',
                            'image',
                            'image_url',
                            'price',                            
                        ]
                    ]
                ]
            );
    }

    public function testFetchAllCategories()
    {
        $this->actingAs($this->admin, 'admin')->getJson(route('admin.core.fetchAll', 'categories'))
            ->assertStatus(200)
            ->assertJsonStructure(
                [
                    'status', 'success',
                    'data'  =>  [
                        0   =>  [
                            'id',
                            'name',
                            'image',
                            'image_url',
                        ]
                    ]
                ]
            );
    }

    public function testFetchAllSubCategories()
    {
        $this->actingAs($this->admin, 'admin')->getJson(route('admin.core.fetchAll', 'subCategory'))
            ->assertStatus(200)
            ->assertJsonStructure(
                [
                    'status', 'success',
                    'data'  =>  [
                        0   =>  [
                            'id',
                            'name',
                            'image',
                            'image_url',
                        ]
                    ]
                ]
            );
    }

    public function testFetchAllShelves()
    {
        $this->actingAs($this->admin, 'admin')->getJson(route('admin.core.fetchAll', 'shelves'))
            ->assertStatus(200)
            ->assertJsonStructure(
                [
                    'status', 'success',
                    'data'  =>  [
                        0   =>  [
                            'id',
                            'name',
                            'image',
                            'image_url',
                        ]
                    ]
                ]
            );
    }

    public function testFetchAllSuppliers()
    {
        $this->actingAs($this->admin, 'admin')->getJson(route('admin.core.fetchAll', 'suppliers'))
            ->assertStatus(200)
            ->assertJsonStructure(
                [
                    'status', 'success',
                    'data'  =>  [
                        0   =>  [
                            'id',
                            'name',
                            'image',
                            'image_url',
                        ]
                    ]
                ]
            );
    }

    public function testFetchAllBrands()
    {
        $this->actingAs($this->admin, 'admin')->getJson(route('admin.core.fetchAll', 'brands'))
            ->assertStatus(200)
            ->assertJsonStructure(
                [
                    'status', 'success',
                    'data'  =>  [
                        0   =>  [
                            'id',
                            'name',
                            'image',
                            'image_url',
                        ]
                    ]
                ]
            );
    }

    public function testFetchAllUnits()
    {
        $this->actingAs($this->admin, 'admin')->getJson(route('admin.core.fetchAll', 'units'))
            ->assertStatus(200)
            ->assertJsonStructure(
                [
                    'status', 'success',
                    'data'  =>  [
                        0   =>  [
                            'id',
                            'name',
                            'image',
                            'image_url',
                        ]
                    ]
                ]
            );
    }

    public function testFetchAllWarehouses()
    {
        $this->actingAs($this->admin, 'admin')->getJson(route('admin.core.fetchAll', 'warehouses'))
            ->assertStatus(200)
            ->assertJsonStructure(
                [
                    'status', 'success',
                    'data'  =>  [
                        0   =>  [
                            'id',
                            'name',
                            'image',
                            'image_url',
                        ]
                    ]
                ]
            );
    }
}
