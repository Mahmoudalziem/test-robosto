<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Webkul\Admin\Repositories\Role\RoleRepository;
use Webkul\Admin\Http\Requests\Role\RoleRequest;
use Illuminate\Support\Facades\Route;
use Webkul\User\Models\PermissionCategory;
use Webkul\User\Models\PermissionCategoryTranslation;
use Webkul\User\Models\Permission;
use Webkul\User\Models\PermissionTranslation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Webkul\User\Models\Role;
use Illuminate\Support\Facades\Cache;

class PermissionsBuild extends Command {

    protected $signature = 'permissions:build';
    protected $description = 'rebuild permissions';
    protected $roleRepository;
    protected $roles;
    protected $storedPermissions = [];

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(RoleRepository $roleRepository) {
        parent::__construct();
        $this->roleRepository = $roleRepository;
        $this->roles = $roleRepository->all();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle() {
        $this->info('###############################################');
        $this->info('Start processing ...');
        $this->storeOldPermissions();
        $this->clear();
        $this->build();
        $this->reStoreOldPermissions();
        $this->info('End processing ...');
        $this->info('================================================');
    }

    private function clear() {

        $this->info('- Start clear permissions .');
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        DB::table('permissions')->truncate();
        DB::table('permission_categories')->truncate();
        DB::table('role_permissions')->truncate();
        DB::table('permission_translations')->truncate();
        DB::table('permission_category_translations')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->info('- Permissions Cleared succesffully!');
    }

    private function build() {


        $routeCollection = Route::getRoutes();
        $permissionsFromDb = Permission::all()->pluck('name')->toArray();
        $routePermissions = [];
        foreach ($routeCollection as $value) {

            $name = explode('.', $value->getName());

            if ($name[0] == 'admin') {
                $roueName = $value->getName();
                $routePermissions[] = $roueName;
            }
        }

        // all perrmisions [route , custom]
        $allPermissions = array_merge($routePermissions, $this->customPermissions());
        // all perrmisions [route + custom] - exludedPermissions
        $permissions = array_diff($allPermissions, $this->exludedPermissions());
        // format permissions
        $permissions = array_values($permissions);
        // only apply new permissions and remove any if exist in db
        $permissions = array_diff($permissions, $permissionsFromDb);
        // format permissions
        $permissions = array_values($permissions);

        // build permissions // new or update
        $permissionCategory = null;
        foreach ($permissions as $permission) {
            $obj = explode('.', $permission);
            $length = count($obj);
            $action = $obj[$length - 1];
            $slug = $obj[$length - 2];

            // loop
            for ($i = 1; $i < $length - 1; $i++) {

                $permissionCategory = PermissionCategory::where('slug', $obj[$i])->first();
                if (!$permissionCategory) {
                    if ($i == 1) {
                        $permissionCategory = PermissionCategory::create([
                                    'slug' => $obj[$i],
                                    'parent_id' => null
                        ]);
                    } else {
                        $parent = PermissionCategory::where('slug', $obj[$i - 1])->first();
                        $permissionCategory = PermissionCategory::create([
                                    'slug' => $obj[$i],
                                    'parent_id' => $parent->id
                        ]);
                    }
                    foreach (core()->getAllLocales() as $locale) {
                        if ($permissionCategory) {
                            PermissionCategoryTranslation::create([
                                'permission_category_id' => $permissionCategory->id,
                                'name' => $obj[$i],
                                'desc' => $obj[$i],
                                'locale' => $locale->code
                            ]);
                        }
                    }
                }
            }


            if ($permissionCategory) {
                $permissonCreate = Permission::create([
                            'route_name' => $permission,
                            'action' => $action,
                            'slug' => $slug,
                            'permission_category_id' => $permissionCategory->id,
                ]);
                foreach (core()->getAllLocales() as $locale) {
                    if ($permissonCreate) {
                        PermissionTranslation::create([
                            'permission_id' => $permissonCreate->id,
                            'name' => $permission,
                            'desc' => $permission,
                            'locale' => $locale->code
                        ]);
                    }
                }
            }
        }

        $this->info('- new permissions built successfully!');
    }

    private function storeOldPermissions() {

        foreach ($this->roles as $role) {
            $this->storedPermissions[$role->slug] = $role->permissions->pluck('route_name');
        }
        Cache::put("stored_permissions", $this->storedPermissions);

        $this->info('- Old role permissions saved successfully! ');
    }

    private function reStoreOldPermissions() {

        $rolePermissions = Cache::get("stored_permissions");
        foreach ($this->roles as $role) {
            $permissions = Permission::whereIn('route_name', $rolePermissions[$role->slug])->get();
            $role->permissions()->sync($permissions);
        }
        $this->info('- Old role permissions restored successfully! ');
    }

    private function exludedPermissions() {
        return Config::get('permissions.exluded_routes');
    }

    private function customPermissions() {
        return Config::get('permissions.custom_permissions');
    }

}
