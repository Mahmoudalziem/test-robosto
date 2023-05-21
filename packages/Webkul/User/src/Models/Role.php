<?php

namespace Webkul\User\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Core\Models\ActivityLogProxy;
use Astrotomic\Translatable\Translatable;
use Webkul\User\Contracts\Role as RoleContract;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;

class Role extends Model implements RoleContract, TranslatableContract {

    use Translatable;

    public const AREA_MANAGER         = 'area-manager';
    public const SUPER_ADMIN           = 'super-admin';
    public const OPERATION_MANAGER           = 'operation-manager';
    public const HR         = 'hr';
    public const DATA_ENTRY   = 'data-entery';
    public const ACCOUNTANT        = 'accountant';
    public const MARKETING          = 'marketing';
    public const CALL_CENTER          = 'call-center';
    
    public $translatedAttributes = [
        'name', 'desc'
    ];
    protected $fillable = ['slug', 'guard_name'];

    /**
     * Get all Logs
     */
    public function logs()
    {
        return $this->morphMany(ActivityLogProxy::modelClass(), 'subject');
    }

    public function permissions() {

        return $this->belongsToMany(PermissionProxy::modelClass(), 'role_permissions');
    }

    public function admins() {

        return $this->belongsToMany(AdminProxy::modelClass(), 'role_admins');
    }

    public function assignPermission($permissions) {
        $this->permissions()->detach();
        return $this->permissions()->attach($permissions);
    }

}
