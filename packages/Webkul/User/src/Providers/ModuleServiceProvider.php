<?php

namespace Webkul\User\Providers;

use Konekt\Concord\BaseModuleServiceProvider;

class ModuleServiceProvider extends BaseModuleServiceProvider {

    protected $models = [
        \Webkul\User\Models\Admin::class,
        \Webkul\User\Models\AdminDeviceToken::class,
        \Webkul\User\Models\Notification::class,
        \Webkul\User\Models\Role::class,
        \Webkul\User\Models\RoleTranslation::class,        
        \Webkul\User\Models\Permission::class,
        \Webkul\User\Models\PermissionTranslation::class,        
        \Webkul\User\Models\PermissionCategory::class,
        \Webkul\User\Models\PermissionCategoryTranslation::class,
        \Webkul\User\Models\AreaManagerTransactionRequest::class,
        \Webkul\User\Models\AreaManagerWallet::class,
        \Webkul\User\Models\AccountantWallet::class,
        \Webkul\User\Models\TransactionTicket::class,
        
    ];

}
