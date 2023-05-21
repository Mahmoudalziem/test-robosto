<?php

namespace Webkul\User\Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

class AdminsTableSeeder extends Seeder {

    public function run() {
        DB::table('admins')->delete();
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
 
        DB::table('admins')->truncate();
 
        
        $user1 = Admin::create([
                    'id' => 1,
                    'name' => 'Super admin',
                    'email' => 'admin@example.com',
                    'username' => 'super-admin',
                    'password' => bcrypt('admin123'),
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                    'status' => 1,
                    'role_id' => 1,
        ]);
        $areas = \Webkul\Area\Models\Area::get();
        foreach ($areas as $area) {
            DB::table('admin_areas')->insert([
                'area_id' => $area->id, 'admin_id' => $user1->id
            ]);
        }
        $warehouses = \Webkul\Inventory\Models\Warehouse::get();
        foreach ($warehouses as $warehouse) {
            DB::table('admin_warehouses')->insert([
                'warehouse_id' => $warehouse->id, 'admin_id' => $user1->id
            ]);
        }

        $user2 = Admin::create([
                    'id' => 2,
                    'name' => 'Robosto operation-manager',
                    'email' => 'operation-manager@robosto.com',
                    'username' => 'operation-manager',
                    'password' => bcrypt('admin123'),
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                    'status' => 1,
                    'role_id' => 2,
        ]);

        $user3 = Admin::create([
                    'id' => 3,
                    'name' => 'Robosto area-manager',
                    'email' => 'area-manager@robosto.com',
                    'username' => 'area-manager',
                    'password' => bcrypt('admin123'),
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                    'status' => 1,
                    'role_id' => 3,
        ]);


        $user4 = Admin::create([
                    'id' => 4,
                    'name' => 'Robosto hr',
                    'email' => 'hr@robosto.com',
                    'username' => 'hr',
                    'password' => bcrypt('admin123'),
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                    'status' => 1,
                    'role_id' => 4,
        ]);
        $user5 = Admin::create([
                    'id' => 5,
                    'name' => 'Robosto dataentery',
                    'email' => 'data-entery@robosto.com',
                    'username' => 'data-entery',
                    'password' => bcrypt('admin123'),
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                    'status' => 1,
                    'role_id' => 5,
        ]);

        $user6 = Admin::create([
                    'id' => 6,
                    'name' => 'Robosto Accountant',
                    'email' => 'accountant@robosto.com',
                    'username' => 'accountant',
                    'password' => bcrypt('admin123'),
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                    'status' => 1,
                    'role_id' => 6,
        ]);

        $user7 = Admin::create([
                    'id' => 7,
                    'name' => 'Robosto Marketing',
                    'email' => 'marketing@robosto.com',
                    'username' => 'marketing',
                    'password' => bcrypt('admin123'),
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                    'status' => 1,
                    'role_id' => 7,
        ]);

        
 

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

}
