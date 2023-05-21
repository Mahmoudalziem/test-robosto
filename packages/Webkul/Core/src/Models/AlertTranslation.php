<?php

namespace Webkul\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Core\Contracts\AlertTranslation as AlertTranslationContract;

class AlertTranslation extends Model implements AlertTranslationContract
{
    
    protected $fillable = [
        'title','body', 'locale'
    ];
}