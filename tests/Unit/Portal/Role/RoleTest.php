<?php

namespace Tests\Unit\Portal\Role;

use Illuminate\Support\Str;
use Tests\TestCase;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;
use Webkul\User\Models\Permission;
use Webkul\User\Models\PermissionCategory;

class RoleTest extends TestCase {

    private $admin;

    public function setUp(): void {
        parent::setUp();
        $this->admin = Admin::find(1);
    }

    public function testCreatePermissions() {

        $data = [];
        $response = $this->actingAs($this->admin, 'admin')->getJson(
                route('admin.permissions.build'),
                $data
        );
        $response
                ->assertStatus(200)
                ->assertJson(['status' => 200])
                ->assertJsonStructure([
                    'status', 'success', 'data'
        ]);
        $permissionCountAfterCreate = Permission::count();
        $permissionCategoryCountAfterCreate = PermissionCategory::count();

        $this->assertDatabaseCount('permissions', $permissionCountAfterCreate);
        $this->assertDatabaseCount('permission_categories', $permissionCategoryCountAfterCreate);
    }

    public function testCreateRoleValidation() {
        $data = [];

        $this->actingAs($this->admin, 'admin')->postJson(
                        route('admin.roles.roles.store'),
                        $data
                )
                ->assertStatus(422)
                ->assertJsonValidationErrors([
                    'slug', 'ar.name', 'en.name', 'permissions'], 'data.errors');
    }

    public function testCreateRole() {
        $roleCountBeforeCreate = Role::count();
        $arName = "Arabic Name " . Str::random(4);
        $enName = "English Name " . Str::random(4);

        $data = [
            'guard_name' => 'admin',
            'slug' => 'super-admin',
            'ar' => [
                'name' => $arName,
                'desc' => $arName,
            ],
            'en' => [
                'name' => $enName,
                'desc' => $enName,
            ],
            'permissions' => Permission::limit(2)->get()->pluck('id')->toArray(),
        ];

        $response = $this->actingAs($this->admin, 'admin')->postJson(
                route('admin.roles.roles.store'),
                $data
        );
        $response
                ->assertStatus(200)
                ->assertJson(['status' => 200])
                ->assertJsonStructure([
                    'status', 'success', 'data'
        ]);

        $newRoleID = $response['data']['id'];

        $this->assertDatabaseCount('roles', $roleCountBeforeCreate + 1);
        $this->assertDatabaseHas('roles', [
            'id' => $newRoleID,
            'slug' => 'super-admin',
        ]);

        $this->assertDatabaseHas('role_translations', [
            'locale' => 'ar',
            'name' => $arName,
        ]);

        $this->assertDatabaseHas('role_translations', [
            'locale' => 'en',
            'name' => $enName,
        ]);
    }

    public function testUpdateRoleValidation() {
        $roleID = Role::latest()->first()->id;
        $data = [];

        $this->actingAs($this->admin, 'admin')->putJson(
                        route('admin.roles.roles.update', $roleID),
                        $data
                )
                ->assertStatus(422)
                ->assertJsonValidationErrors(['slug', 'ar.name', 'en.name', 'permissions'], 'data.errors');
    }

    public function testUpdateRole() {
        $role = Role::latest()->first();
        $roleID = Role::latest()->first()->id;

        $arName = "Arabic Name " . Str::random(4);
        $enName = "English Name " . Str::random(4);

        $data = [
            'slug' => 'super-admin',
            'ar' => [
                'name' => $arName,
                'desc' => $arName,
            ],
            'en' => [
                'name' => $enName,
                'desc' => $enName,
            ],
            'permissions' => Permission::limit(2)->get()->pluck('id')->toArray(),
        ];

        $response = $this->actingAs($this->admin, 'admin')->putJson(
                route('admin.roles.roles.update', $roleID),
                $data
        );

        $response
                ->assertStatus(200)
                ->assertJson(['status' => 200])
                ->assertJsonStructure([
                    'status', 'success', 'data'
        ]);
    }

    public function testListRoles() {

        $this->actingAs($this->admin, 'admin')->getJson(route('admin.roles.roles.index'))
                ->assertStatus(200)
                ->assertJson(['success' => true])
                ->assertJsonStructure(
                        [
                            'status', 'success', 'message',
                            'data' => [
                                0 => [
                                    'id',
                                    'slug',
                                    'name',
                                    'permissions',
                                    'created_at',
                                    'updated_at',
                                ]
                            ]
                        ]
        );
    }

    public function testShowRole() {
        $role = Role::first();

        $this->actingAs($this->admin, 'admin')->getJson(
                        route('admin.roles.roles.show', $role)
                )
                ->assertStatus(200)
                ->assertJson(['success' => true])
                ->assertJsonStructure([
                    'status',
                    'success',
                    'data' => [
                        'id',
                        'slug',
                        'name',
                        'permissions',
                        'created_at',
                        'updated_at',
                    ]
        ]);
    }

}
