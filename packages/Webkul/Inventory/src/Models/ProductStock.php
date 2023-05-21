<?php

namespace Webkul\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Inventory\Contracts\ProductStock as ProductStockContract;

class ProductStock extends Model implements ProductStockContract
{
    protected $fillable = ['inventory_control_id','area_id','warehouse_id','product_id','inventory_qty','shipped_qty','qty','qty_stock','valid','status','is_default'];
}