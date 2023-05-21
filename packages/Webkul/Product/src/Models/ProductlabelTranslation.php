<?php

namespace Webkul\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Product\Contracts\ProductlabelTranslation as ProductlabelTranslationContract;

class ProductlabelTranslation extends Model implements ProductlabelTranslationContract
{
    protected $table = 'productlabel_translations';

    public $timestamps = false;

    protected $fillable = [
        'name', 'locale'
    ];
}