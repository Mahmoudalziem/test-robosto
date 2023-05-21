<?php

namespace Webkul\Customer\Http\Middleware;

use Closure;
use Illuminate\Console\Scheduling\Event;
use Webkul\Core\Http\Controllers\BackendBaseController;
use Webkul\Customer\Models\Customer;

class CustomerMustBeActive extends BackendBaseController
{

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = 'customer')
    {

        // only get token with login route
        if ($request->route()->getName() == 'app.customer.login') {
            $customer = Customer::where(
                [
                    'phone' => $request->only('phone'),
                    'status' => 0,
                ]
            )->first();
            if ($customer) {
                return $this->responseError(403, 'Your account has been deacativated, please contact the Robosto Support');
            }
        }

        $user = auth($guard)->user();

        if ($user && $user->status == 0) {

            auth($guard)->invalidate(true);
            return $this->responseError(403, 'Your account has been deacativated, please contact the Robosto Support');
        }
        return $next($request);
    }
}