<?php

namespace Webkul\Core\Http\Middleware;

use Closure;
use Illuminate\Console\Scheduling\Event;
use Webkul\Core\Http\Controllers\BackendBaseController;

class MustBeActive extends BackendBaseController
{

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {

        // only get token with login route
        if ($request->route()->getName() == 'admin.api.login') {
            $token = auth('admin')->attempt($request->only('email', 'password'));
        }

        $user = auth('admin')->user();

        if ($user && $user->status == 0) {

            auth('admin')->invalidate(true);
            return $this->responseError(403, 'Your account has been suspended please contact the administrator');
        }
        return $next($request);
    }
}