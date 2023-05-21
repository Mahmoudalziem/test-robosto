<?php

namespace Webkul\Core\Http\Middleware;

use Closure;
use Illuminate\Console\Scheduling\Event;
use Webkul\Core\Http\Controllers\BackendBaseController;
use Illuminate\Support\Facades\Route;

class PermissionGate extends BackendBaseController {

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next) {


        $user = auth('admin');

        if ($user->check()) {
 
            $permissions = $user->user()->getRolePermissionNames();
 
            $routeName = Route::currentRouteName();
 
            $permissions=array_merge($permissions,config()->get('permissions.exluded_routes'));
 
            if (in_array($routeName, $permissions) ||  $user->user()->hasRole(['super-admin'])) {
                return $next($request);
            } else {
                return $this->responseError(403, 'You have no access for this route!');
            }
        }
    }

}
