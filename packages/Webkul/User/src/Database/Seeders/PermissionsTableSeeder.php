<?php

namespace Webkul\User\Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;
use Webkul\User\Models\Role;

class PermissionsTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('permissions')->delete();

 

        DB::table('permissions')->insert([
            'id'              => 1,
            'name'            => 'can update',
            'slug'            => 'can-update',
        ]);
    }
}