<?php

namespace Webkul\User\Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;
use Webkul\User\Models\Role;
use Webkul\User\Models\RoleTranslation;
use Webkul\User\Models\Admin;

class RolesTableSeeder extends Seeder {

    public function run() {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        DB::table('roles')->truncate();
        DB::table('role_translations')->truncate();
        $role1 = Role::create([
                    'id' => 1,
                    'slug' => 'super-admin',
                    'guard_name' => 'admin',
                    'ar' => ['name' => 'super-admin', 'desc' => 'super-admin'],
                    'en' => ['name' => 'super-admin', 'desc' => 'super-admin'],
        ]);


        $role2 = Role::create([
                    'id' => 2,
                    'slug' => 'operation-manager',
                    'guard_name' => 'admin',
                    'ar' => ['name' => 'operation-manager', 'desc' => 'operation-manager'],
                    'en' => ['name' => 'operation-manager', 'desc' => 'operation-manager'],
        ]);

        $role3 = Role::create([
                    'id' => 3,
                    'slug' => 'area-manager',
                    'guard_name' => 'admin',
                    'ar' => ['name' => 'area-manager', 'desc' => 'area-manager'],
                    'en' => ['name' => 'rea-manager', 'desc' => 'area-manager'],
        ]);

        $role4 = Role::create([
                    'id' => 4,
                    'slug' => 'hr',
                    'guard_name' => 'admin',
                    'ar' => ['name' => 'hr', 'desc' => 'hr'],
                    'en' => ['name' => 'hr', 'desc' => 'hr'],
        ]);


        $role5 = Role::create([
                    'id' => 5,
                    'slug' => 'data-entery',
                    'guard_name' => 'admin',
                    'ar' => ['name' => 'data-entery', 'desc' => 'data-entery'],
                    'en' => ['name' => 'data-entery', 'desc' => 'data-entery'],
        ]);

        $role6 = Role::create([
                    'id' => 6,
                    'slug' => 'accountant',
                    'guard_name' => 'admin',
                    'ar' => ['name' => 'accountant', 'desc' => 'accountant'],
                    'en' => ['name' => 'accountant', 'desc' => 'accountant'],
        ]);

        $role7 = Role::create([
                    'id' => 7,
                    'slug' => 'marketing',
                    'guard_name' => 'admin',
                    'ar' => ['name' => 'marketing', 'desc' => 'marketing'],
                    'en' => ['name' => 'marketing', 'desc' => 'marketing'],
        ]);

        DB::table('admin_roles')->truncate();
 
        $users = Admin::get();
        foreach ($users as $user) {
            $user->assignRole($role1);
        }


        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

}
