<?php

namespace Webkul\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Product\Models\ProductProxy;
use Webkul\Core\Contracts\Config as ConfigContract;

class Config extends Model implements ConfigContract
{
    protected $table = 'config';

    public $timestamps = false;

    public const LAST_SKU_NUMBER = 'last_sku_number';

    protected $fillable = ['key', 'value'];
}