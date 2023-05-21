<?php

namespace Webkul\Brand\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Brand\Contracts\BrandTranslation as BrandTranslationContract;

class BrandTranslation extends Model implements BrandTranslationContract
{

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'brand_translations';

    public $timestamps = false;

    protected $fillable = [
        'name', 'locale'
    ];
}
