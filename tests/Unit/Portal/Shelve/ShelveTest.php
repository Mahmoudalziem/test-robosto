<?php

namespace Tests\Unit\Portal\Shelve;

use Tests\TestCase;
use Webkul\User\Models\Admin;
use Illuminate\Support\Facades\Event;
use Webkul\Core\Models\Shelve;

class ShelveTest extends TestCase
{
    private $admin;

    public function setUp(): void
    {
        parent::setUp();
        $this->admin = Admin::find(1);
    }

    public function testGettingShelves()
    {
        
        $this->actingAs($this->admin, 'admin')->getJson(route('admin.inventory.shelves.index'))
            ->assertStatus(200)
            ->assertJson(['success' =>  true])
            ->assertJsonStructure(
                [
                    'status', 'success', 'message',
                    'data'  =>  [
                        0   =>  [
                            'id',
                            'name',
                            'row',
                            'position',
                            'products'
                        ]
                    ]
                ]
            );
    }

    public function testShowShelve()
    {
        $shelve = Shelve::first();

        $this->actingAs($this->admin, 'admin')->getJson(
            route('admin.inventory.shelves.show', $shelve)
        )
            ->assertStatus(200)
            ->assertJson(['success' =>  true])
            ->assertJsonStructure([
                'status',
                'success',
                'data'  =>  [
                    'id',
                    'name',
                    'row',
                    'position',
                    'products'
                ]
            ]);
    }


    public function testCreateShelveValidation()
    {
        $data = [];

        $this->actingAs($this->admin, 'admin')->postJson(
            route('admin.inventory.shelves.store'),
            $data
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'name', 'position', 'row'
            ], 'data.errors');
    }

    public function testCreateShelve()
    {
        Event::fake();

        $data = [
            'name'  =>  'D',
            'position' =>  4,
            'row' =>  1
        ];

        $this->actingAs($this->admin, 'admin')->postJson(
            route('admin.inventory.shelves.store'),
            $data
        )
            ->assertStatus(200)
            ->assertJson(['status'    =>  200])
            ->assertJsonStructure([
                'status', 'success', 'data'
            ]);

        $this->assertDatabaseHas('shelves', [
            'name'  =>  'D',
            'position' => 4,
        ]);
    }

    public function testUpdateShelveValidation()
    {
        $shelveID = Shelve::latest()->first()->id;
        $data = [];

        $this->actingAs($this->admin, 'admin')->postJson(
            route('admin.inventory.shelves.update', $shelveID),
            $data
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'name', 'position', 'row'
            ], 'data.errors');
    }

    public function testUpdateShelve()
    {
        $shelveID = Shelve::latest()->first()->id;
        $data = [
            'name'  =>  'D',
            'position' =>  4,
            'row' =>  3
        ];

        $this->actingAs($this->admin, 'admin')->postJson(
            route('admin.inventory.shelves.update', $shelveID),
            $data
        )
            ->assertStatus(200)
            ->assertJson(['status'    =>  200])
            ->assertJsonStructure([
                'status', 'success', 'data'
            ]);

        $this->assertDatabaseHas('shelves', [
            'id'  =>  $shelveID,
            'row' => 3,
        ]);
    }


    public function testDeleteShelve()
    {
        $shelveID = Shelve::latest()->first()->id;

        $this->actingAs($this->admin, 'admin')->postJson(route('admin.inventory.shelves.delete', $shelveID))
            ->assertStatus(200)
            ->assertJson(['status'    =>  200])
            ->assertJsonStructure([
                'status', 'success', 'data'
            ]);

        $this->assertDatabaseMissing('shelves', [
            'id'  =>  $shelveID,
        ]);
    }
}
