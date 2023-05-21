<?php

namespace Webkul\User\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\User\Contracts\Permission as PermissionContract;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Webkul\User\Models\PermissionCategoryProxy;
class Permission extends Model implements PermissionContract, TranslatableContract {

    use Translatable;

    public $translatedAttributes = [
        'name', 'desc'
    ];
    protected $fillable = ['route_name', 'action', 'slug','permission_category_id'];

    public function roles() {

        return $this->belongsToMany(RoleProxy::modelClass(), 'role_permissions');
    }
    
    public function category() {

        return $this->belongsTo(PermissionCategoryProxy::modelClass(), 'permission_category_id');
    }    

}
