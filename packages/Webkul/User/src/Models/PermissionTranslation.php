<?php

namespace Webkul\User\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\User\Contracts\PermissionTranslation as PermissionTranslationContract;

class PermissionTranslation extends Model implements PermissionTranslationContract {

    protected $fillable = ['permission_id', 'name', 'desc','locale'];

}
