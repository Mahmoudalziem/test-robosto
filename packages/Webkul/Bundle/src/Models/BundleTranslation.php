<?php

namespace Webkul\Bundle\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Bundle\Contracts\BundleTranslation as BundleTranslationContract;

class BundleTranslation extends Model implements BundleTranslationContract
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'bundle_translations';

    public $timestamps = false;

    protected $fillable = [
        'name', 'description', 'locale'
    ];
}
