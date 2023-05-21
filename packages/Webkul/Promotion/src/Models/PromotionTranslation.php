<?php

namespace Webkul\Promotion\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Product\Models\ProductProxy;
use Webkul\Promotion\Contracts\PromotionTranslation as PromotionTranslationContract;

class PromotionTranslation extends Model implements PromotionTranslationContract
{

    protected $fillable = [
        'title', 'description', 'locale'
    ];

}