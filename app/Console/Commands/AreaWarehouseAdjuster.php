<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Webkul\Admin\Repositories\Role\RoleRepository;
use Webkul\Admin\Http\Requests\Role\RoleRequest;
use Illuminate\Support\Facades\Route;
use Webkul\User\Models\PermissionCategory;
use Webkul\User\Models\PermissionCategoryTranslation;
use Webkul\User\Models\Permission;
use Webkul\User\Models\PermissionTranslation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Webkul\User\Models\Role;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Webkul\Inventory\Models\InventoryWarehouse;

class AreaWarehouseAdjuster extends Command {

    protected $signature = 'area-warehouse:adjust';
    protected $description = 'adjust quantities';

    public function handle() {
        $workingWarehouses = ["1"=>2 , "2"=>3 , "3"=>4 , "4"=>6 , "6"=>8 , "8"=>11];
        $select = "SELECT 
        inventory_areas.area_id , qty as 'warehouse_qty' , total_qty as 'area_qty' , T.product_id
     FROM
         (SELECT 
             product_id, area_id, SUM(qty) AS 'qty'
         FROM
             inventory_warehouses where bundle_id is null
         GROUP BY product_id , area_id) T
             INNER JOIN
         inventory_areas ON T.product_id = inventory_areas.product_id
             AND T.area_id = inventory_areas.area_id
     WHERE
         inventory_areas.total_qty > T.qty
             ";
        $select = preg_replace("/\r|\n/", "", $select);
        $toBeAdjustedProducts = collect(DB::select(DB::raw($select)));
        foreach($toBeAdjustedProducts as $product){
            if(isset($workingWarehouses[$product->area_id]) && !empty($workingWarehouses[$product->area_id])){
                Log::info(json_encode($product));
                Log::info(["qty"=>$product->warehouse_qty,"product_id"=>$product->product_id , "warehouse_id"=>$workingWarehouses[$product->area_id]]);
                InventoryWarehouse::where(["qty"=>$product->warehouse_qty,"product_id"=>$product->product_id , "warehouse_id"=>$workingWarehouses[$product->area_id]])->update(["qty"=>$product->area_qty]);
            }
        }
        Log::info("done adjusting");
    }
}
