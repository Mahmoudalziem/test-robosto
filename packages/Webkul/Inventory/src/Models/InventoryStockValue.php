<?php

namespace Webkul\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Inventory\Contracts\InventoryStockValue as InventoryStockValueContract;

class InventoryStockValue extends Model implements InventoryStockValueContract
{
    protected $fillable = ['area_id','warehouse_id','amount_before_discount','amount','build_date'];
}