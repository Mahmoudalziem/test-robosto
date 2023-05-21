<?php

namespace Webkul\Supplier\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Product\Models\ProductProxy;
use Webkul\Supplier\Contracts\SupplierArea as SupplierAreaContract;

class SupplierArea extends Model implements SupplierAreaContract
{
    protected $fillable = [];
}