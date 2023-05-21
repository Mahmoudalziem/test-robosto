<?php

namespace Webkul\User\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\User\Contracts\PermissionCategoryTranslation as PermissionCategoryTranslationContract;

class PermissionCategoryTranslation extends Model implements PermissionCategoryTranslationContract {

    protected $fillable = ['permission_category_id', 'name', 'desc','locale'];

}
