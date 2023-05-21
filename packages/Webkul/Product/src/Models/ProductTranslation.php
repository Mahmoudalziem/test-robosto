<?php

namespace Webkul\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Product\Models\ProductProxy;
use Webkul\Product\Contracts\ProductTranslation as ProductTranslationContract;

class ProductTranslation extends Model implements ProductTranslationContract
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'product_translations';

    public $timestamps = false;

    protected $fillable = [
        'name', 'description', 'locale'
    ];
}