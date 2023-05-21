<?php

namespace Webkul\Category\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Category\Contracts\CategoryTranslation as CategoryTranslationContract;

class CategoryTranslation extends Model implements CategoryTranslationContract
{

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'category_translations';

    public $timestamps = false;

    protected $fillable = [
        'name',  'locale'
    ];
}