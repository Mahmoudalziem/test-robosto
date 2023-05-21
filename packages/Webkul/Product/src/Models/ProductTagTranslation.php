<?php

namespace Webkul\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Product\Contracts\ProductTagTranslation as ProductTagTranslationContract;

class ProductTagTranslation extends Model implements ProductTagTranslationContract
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'product_tag_translations';

    public $timestamps = false;

    protected $fillable = [
        'name', 'locale'
    ];
}