<?php

namespace Webkul\Inventory\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class WarehouseTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('warehouses')->delete();
        DB::table('warehouse_translations')->delete();

        $warehouses = array(
            array(
                "id" => 1,
                "contact_name" => "Warehouse A",
                "contact_email" => "warehouse1@example.com",
                "contact_number" => "1234567899",
                "address" => "1234567899",
                "latitude" => 29.981175,
                "longitude" => 31.279402,
                "is_main" => 0,
                "status" => 1,
                "area_id" => 1,
                "created_at" => null,
                "updated_at" => null
            ),
            array(
                "id" => 2,
                "contact_name" => "Warehouse B",
                "contact_email" => "warehouse2@example.com",
                "contact_number" => "1234567899",
                "address" => "1234567899",
                "latitude" => 29.981175,
                "longitude" => 31.279402,
                "is_main" => 0,
                "status" => 1,
                "area_id" => 1,
                "created_at" => null,
                "updated_at" => null
            )
        );
        DB::table('warehouses')->insert($warehouses);

        $warehouse_translations = array(
            array(
                "id" => 1,
                "name" => "Warehouse A",
                "description" => null,
                "locale" => "ar",
                "warehouse_id" => 1
            ),
            array(
                "id" => 2,
                "name" => "Warehouse A",
                "description" => null,
                "locale" => "en",
                "warehouse_id" => 1
            ),
            array(
                "id" => 3,
                "name" => "Warehouse B",
                "description" => null,
                "locale" => "ar",
                "warehouse_id" => 2
            ),
            array(
                "id" => 4,
                "name" => "Warehouse B",
                "description" => null,
                "locale" => "en",
                "warehouse_id" => 2
            )
        );
        DB::table('warehouse_translations')->insert($warehouse_translations);
    }
}
