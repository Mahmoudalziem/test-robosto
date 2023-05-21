<?php

namespace Webkul\Area\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Area\Contracts\AreaTranslation as AreaTranslationContract;
class AreaTranslation extends Model implements AreaTranslationContract
{
     protected $fillable = ['name'];
}
