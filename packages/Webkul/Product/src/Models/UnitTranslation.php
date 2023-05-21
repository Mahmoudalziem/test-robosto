<?php

namespace Webkul\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Product\Models\ProductProxy;
use Webkul\Product\Contracts\UnitTranslation as UnitTranslationContract;

class UnitTranslation extends Model implements UnitTranslationContract
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'unit_translations';

    public $timestamps = false;

    protected $fillable = [
        'name', 'locale'
    ];
}