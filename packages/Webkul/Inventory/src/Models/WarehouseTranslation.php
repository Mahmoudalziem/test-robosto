<?php

namespace Webkul\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Product\Models\ProductProxy;
use Webkul\Inventory\Contracts\WarehouseTranslation as WarehouseTranslationContract;

class WarehouseTranslation extends Model implements WarehouseTranslationContract
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'warehouse_translations';

    public $timestamps = false;

    protected $fillable = [
        'name', 'description', 'locale'
    ];
}