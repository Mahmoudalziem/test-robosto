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
use Webkul\Inventory\Models\InventoryArea;
use Webkul\Inventory\Models\InventoryWarehouse;

class AreaTotalQuantityAdjuster extends Command {

    protected $signature = 'area-qty:adjust';
    protected $description = 'adjust quantities';

    public function handle() {
        $notToBeUpdatesSelect = "SELECT product_id from (select id from orders where status in ('pending','scheduled') and shippment_id is null)O inner join order_items on O.id = order_items.order_id;";
        $notToBeUpdatesQuery=preg_replace("/\r|\n/", "", $notToBeUpdatesSelect);
        $notToBeUpdatesQueryProducts = collect(DB::select(DB::raw($notToBeUpdatesQuery)));
        $notToBeReducedProductsArray = $notToBeUpdatesQueryProducts->pluck('product_id')->toArray();
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
         inventory_areas.total_qty < T.qty
             ";
        $select = preg_replace("/\r|\n/", "", $select);
        $toBeAdjustedProducts = collect(DB::select(DB::raw($select)));
        foreach($toBeAdjustedProducts as $product){
            if(!in_array($product->product_id,$notToBeReducedProductsArray)){
                Log::info(json_encode($product));
                Log::info(["warehouse_qty"=>$product->warehouse_qty,"area_qty"=>$product->area_qty,"product_id"=>$product->product_id]);
                InventoryArea::where(
                    [
                        'area_id'=>$product->area_id,
                        'total_qty'=>$product->area_qty,
                        'product_id'=>$product->product_id
                    ]
                    )->update(["total_qty"=>$product->warehouse_qty]);
            }
        }
        Log::info("done adjusting");
    }
}