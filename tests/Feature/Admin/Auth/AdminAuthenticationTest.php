<?php

namespace Tests\Feature\Admin\Auth;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;
use Webkul\User\Database\Seeders\AdminsTableSeeder;


class AdminAuthenticationTest extends TestCase
{

    public function testAdminAuthLoginValidation()
    {
        $response = $this->postJson(route('admin.api.login'), []);
        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password'], 'data.errors');
    }

    public function testAdminAuthLogin()
    {
        $loginData = ['email' => 'admin@example.com', 'password' => 'admin123'];

        $response = $this->postJson(route('admin.api.login'), $loginData);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'success',
                'data'  =>  [
                    'access_token',
                    'token_type',
                    'expires_in',
                    'user'  =>  [
                        "id",
                        "name",
                        "email",
                        "is_verified",
                        "status",
                        "roles",
                        "created_at",
                        "updated_at",
                    ]
                ]
            ]);
    }
}
