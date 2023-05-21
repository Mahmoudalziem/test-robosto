<?php

namespace Webkul\Driver\Http\Middleware;

use Closure;
use Illuminate\Console\Scheduling\Event;
use Webkul\Core\Http\Controllers\BackendBaseController;
use Webkul\Driver\Models\Driver;

class DriverMustBeActive extends BackendBaseController
{

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = 'driver')
    {
        
        // only get token with login route
        if ($request->route()->getName() == 'app.driver.login') {
               
            $Driver = Driver::where(
                [
                    'username' => $request['username'],
                    'status' => 0,
                ]
            )->first();
           
            if ($Driver) {
                return $this->responseError(403, 'لايمكنك تسجيل الدخول من فضلك تواصل مع الأدراة');
            }
        }

        $user = auth($guard)->user();

        if ($user && $user->status == 0) {

            auth($guard)->invalidate(true);
            return $this->responseError(403, 'لايمكنك تسجيل الدخول من فضلك تواصل مع الأدراة');
        }
        return $next($request);
    }
}