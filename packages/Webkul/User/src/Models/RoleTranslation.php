<?php

namespace Webkul\User\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\User\Contracts\RoleTranslation as RoleTranslationContract;

class RoleTranslation extends Model implements RoleTranslationContract
{
    protected $fillable = ['name','desc'];
}