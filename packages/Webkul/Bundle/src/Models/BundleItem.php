<?php

namespace Webkul\Bundle\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Bundle\Contracts\BundleItem as BundleItemContract;
use Webkul\Product\Models\ProductProxy;

class BundleItem extends Model implements BundleItemContract
{
    protected $fillable = ['quantity', 'original_price', 'bundle_price', 'total_original_price', 'total_bundle_price', 'bundle_id', 'product_id'];

    public function bundle()
    {
        return $this->belongsTo(BundleProxy::modelClass());
    }

    public function product()
    {
        return $this->belongsTo(ProductProxy::modelClass());
    }
}
