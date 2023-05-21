<?php

namespace Webkul\Category\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Category\Contracts\SubCategoryTranslation as SubCategoryTranslationContract;

class SubCategoryTranslation extends Model implements SubCategoryTranslationContract
{

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sub_category_translations';

    public $timestamps = false;

    protected $fillable = [
        'name', 'locale'
    ];
}