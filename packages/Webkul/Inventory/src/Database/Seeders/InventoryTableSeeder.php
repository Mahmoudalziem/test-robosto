<?php

namespace Webkul\Inventory\Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class InventoryTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('inventory_areas')->delete();
        DB::table('inventory_warehouses')->delete();
        DB::table('inventory_products')->delete();

        $inventoryAreas =
        array(
            array('id' => '1', 'area_id' => '1', 'product_id' => '1', 'init_total_qty' => '500', 'total_qty' => '500', 'created_at' => '2020-12-20 21:53:43', 'updated_at' => '2021-01-04 15:49:40'),
            array('id' => '4', 'area_id' => '1', 'product_id' => '2', 'init_total_qty' => '500', 'total_qty' => '500', 'created_at' => '2020-12-20 21:55:11', 'updated_at' => '2021-01-04 15:49:40'),
            array('id' => '2', 'area_id' => '1', 'product_id' => '3', 'init_total_qty' => '500', 'total_qty' => '500', 'created_at' => '2020-12-20 21:53:43', 'updated_at' => '2021-01-04 16:02:17'),
            array('id' => '9', 'area_id' => '1', 'product_id' => '4', 'init_total_qty' => '500', 'total_qty' => '500', 'created_at' => '2020-12-20 21:53:43', 'updated_at' => '2021-01-04 16:02:17'),
            array('id' => '5', 'area_id' => '2', 'product_id' => '1', 'init_total_qty' => '500', 'total_qty' => '500', 'created_at' => '2020-12-20 21:53:43', 'updated_at' => '2021-01-04 15:49:40'),
            array('id' => '6', 'area_id' => '2', 'product_id' => '2', 'init_total_qty' => '500', 'total_qty' => '500', 'created_at' => '2020-12-20 21:55:11', 'updated_at' => '2021-01-04 15:49:40'),
            array('id' => '7', 'area_id' => '2', 'product_id' => '3', 'init_total_qty' => '500', 'total_qty' => '500', 'created_at' => '2020-12-20 21:53:43', 'updated_at' => '2021-01-04 16:02:17'),
        );
        DB::table('inventory_areas')->insert($inventoryAreas);

        $inventoryWarehouse =
        array(
            array('id' => '1', 'product_id' => '1', 'warehouse_id' => '1', 'area_id' => '1', 'qty' => '400', 'can_order' => '1', 'created_at' => '2020-12-20 21:53:43', 'updated_at' => '2021-01-04 16:02:43'),
            array('id' => '4', 'product_id' => '2', 'warehouse_id' => '1', 'area_id' => '1', 'qty' => '380', 'can_order' => '1', 'created_at' => '2020-12-20 21:55:11', 'updated_at' => '2021-01-04 16:02:43'),
            array('id' => '2', 'product_id' => '3', 'warehouse_id' => '1', 'area_id' => '1', 'qty' => '360', 'can_order' => '1', 'created_at' => '2020-12-20 21:53:43', 'updated_at' => '2021-01-04 16:02:43'),
            array('id' => '5', 'product_id' => '4', 'warehouse_id' => '1', 'area_id' => '1', 'qty' => '260', 'can_order' => '1', 'created_at' => '2020-12-20 21:53:43', 'updated_at' => '2021-01-04 16:02:43'),
            array('id' => '8', 'product_id' => '1', 'warehouse_id' => '2', 'area_id' => '1', 'qty' => '100', 'can_order' => '1', 'created_at' => '2020-12-20 21:53:43', 'updated_at' => '2021-01-04 16:02:43'),
            array('id' => '10', 'product_id' => '2', 'warehouse_id' => '2', 'area_id' => '1', 'qty' => '120', 'can_order' => '1', 'created_at' => '2020-12-20 21:55:11', 'updated_at' => '2021-01-04 16:02:43'),
            array('id' => '9', 'product_id' => '3', 'warehouse_id' => '2', 'area_id' => '1', 'qty' => '140', 'can_order' => '1', 'created_at' => '2020-12-20 21:53:43', 'updated_at' => '2021-01-04 16:02:43'),
            array('id' => '11', 'product_id' => '4', 'warehouse_id' => '2', 'area_id' => '1', 'qty' => '240', 'can_order' => '1', 'created_at' => '2020-12-20 21:53:43', 'updated_at' => '2021-01-04 16:02:43'),
        );
        DB::table('inventory_warehouses')->insert($inventoryWarehouse);

        $inventoryProducts =
        array(
            array('id' => '1', 'sku' => 'PR1', 'prod_date' => '2020-12-08', 'exp_date' => '2021-03-31', 'qty' => '100', 'cost_before_discount' => '10.00', 'cost' => '10.00', 'amount_before_discount' => '1000.00', 'amount' => '1000.00', 'product_id' => '1', 'warehouse_id' => '1', 'area_id' => '1', 'created_at' => '2020-12-20 21:53:43', 'updated_at' => '2020-12-29 13:03:13'),
            array('id' => '2', 'sku' => 'PR3', 'prod_date' => '2020-12-01', 'exp_date' => '2021-03-31', 'qty' => '160', 'cost_before_discount' => '15.00', 'cost' => '15.00', 'amount_before_discount' => '2400.00', 'amount' => '2400.00', 'product_id' => '3', 'warehouse_id' => '1', 'area_id' => '1', 'created_at' => '2020-12-20 21:53:43', 'updated_at' => '2021-01-04 16:02:43'),
            array('id' => '4', 'sku' => 'PR2', 'prod_date' => '2020-12-08', 'exp_date' => '2021-03-31', 'qty' => '300', 'cost_before_discount' => '10.00', 'cost' => '10.00', 'amount_before_discount' => '3000.00', 'amount' => '3000.00', 'product_id' => '2', 'warehouse_id' => '1', 'area_id' => '1', 'created_at' => '2020-12-20 21:55:11', 'updated_at' => '2021-01-04 16:02:43'),
            array('id' => '3', 'sku' => 'PR4', 'prod_date' => '2020-12-08', 'exp_date' => '2021-03-31', 'qty' => '160', 'cost_before_discount' => '10.00', 'cost' => '10.00', 'amount_before_discount' => '3000.00', 'amount' => '3000.00', 'product_id' => '2', 'warehouse_id' => '1', 'area_id' => '1', 'created_at' => '2020-12-20 21:55:11', 'updated_at' => '2021-01-04 16:02:43'),
            array('id' => '5', 'sku' => 'li1', 'prod_date' => '2020-12-01', 'exp_date' => '2021-03-31', 'qty' => '120', 'cost_before_discount' => '1.40', 'cost' => '1.12', 'amount_before_discount' => '168.00', 'amount' => '134.40', 'product_id' => '1', 'warehouse_id' => '1', 'area_id' => '1', 'created_at' => '2020-12-23 14:01:00', 'updated_at' => '2021-01-04 13:42:47'),
            array('id' => '6', 'sku' => 'DO1', 'prod_date' => '2020-12-01', 'exp_date' => '2021-03-31', 'qty' => '80', 'cost_before_discount' => '1.40', 'cost' => '1.12', 'amount_before_discount' => '112.00', 'amount' => '89.60', 'product_id' => '2', 'warehouse_id' => '1', 'area_id' => '1', 'created_at' => '2020-12-23 14:01:00', 'updated_at' => '2020-12-23 14:01:00'),
            array('id' => '7', 'sku' => 'ST1', 'prod_date' => '2020-12-01', 'exp_date' => '2021-03-31', 'qty' => '200', 'cost_before_discount' => '1.40', 'cost' => '1.12', 'amount_before_discount' => '280.00', 'amount' => '224.00', 'product_id' => '3', 'warehouse_id' => '1', 'area_id' => '1', 'created_at' => '2020-12-23 14:01:00', 'updated_at' => '2020-12-27 14:11:18'),
            array('id' => '9', 'sku' => 'li7', 'prod_date' => '2020-12-01', 'exp_date' => '2021-03-31', 'qty' => '180', 'cost_before_discount' => '2.00', 'cost' => '2.00', 'amount_before_discount' => '360.00', 'amount' => '360.00', 'product_id' => '1', 'warehouse_id' => '1', 'area_id' => '1', 'created_at' => '2020-12-23 14:02:40', 'updated_at' => '2021-01-04 16:02:43'),
            array('id' => '13', 'sku' => 'ty4', 'prod_date' => '2020-12-08', 'exp_date' => '2021-03-31', 'qty' => '100', 'cost_before_discount' => '10.00', 'cost' => '10.00', 'amount_before_discount' => '3000.00', 'amount' => '3000.00', 'product_id' => '4', 'warehouse_id' => '1', 'area_id' => '1', 'created_at' => '2020-12-20 21:55:11', 'updated_at' => '2021-01-04 16:02:43'),
        );
        DB::table('inventory_products')->insert($inventoryProducts);
    }
}