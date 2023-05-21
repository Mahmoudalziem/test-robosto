<?php

namespace Webkul\User\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\User\Contracts\PermissionCategory as PermissionCategoryContract;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Webkul\User\Models\PermissionCategoryProxy;

class PermissionCategory extends Model implements PermissionCategoryContract, TranslatableContract {

    use Translatable;
   

 
    public $translatedAttributes = [
        'name', 'desc'
    ];
    protected $fillable = ['slug', 'parent_id'];

    public function parent() {
        return $this->belongsTo(PermissionCategoryProxy::modelClass(), 'parent_id')->where('parent_id', 0)->with('parent');
    }

    public function directParent() {
        return $this->belongsTo(PermissionCategoryProxy::modelClass(), 'parent_id')->with('parent');
    }

// all ascendants
    public function parent_rec() {
        return $this->parent()->with('parent_rec');
    }

    public function children() {
        return $this->hasMany(PermissionCategoryProxy::modelClass(), 'parent_id')->with('children');
    }

    public function children_rec() {
        return $this->children()->with('children_rec');
    }

//    public function childrenWithPermissions() {
//        return $this->hasMany(PermissionCategoryProxy::modelClass(), 'parent_id')->with('children');
//    }

    public function permissions() {
        return $this->hasMany(PermissionProxy::modelClass());
    }

}
