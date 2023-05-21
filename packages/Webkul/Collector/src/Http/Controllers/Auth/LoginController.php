<?php

namespace Webkul\Collector\Http\Controllers\Auth;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Event;
use Webkul\Collector\Http\Requests\CollectorLoginRequest;
use Webkul\Collector\Http\Resources\Collector\CollectorSignle;
use Webkul\Collector\Repositories\CollectorRepository;
use Webkul\Core\Http\Controllers\BackendBaseController;
use Webkul\User\Models\Role;
use Webkul\Collector\Models\Collector;
use Webkul\Sales\Models\Order;

class LoginController extends BackendBaseController {

    protected $collectorRepository;
    protected $guard;

    public function __construct(CollectorRepository $collectorRepository) {
        $this->collectorRepository = $collectorRepository;
        $this->guard = 'collector';
        auth()->setDefaultDriver($this->guard);
    }

    public function login(CollectorLoginRequest $request) {
        $jwtToken = null;
        if (!$jwtToken = auth()->guard()->attempt($request->only('username', 'password'))) {
            return $this->responseError(401, "Invalid Username or Password");
        }

        $collector = auth($this->guard)->user();
        if ($collector->is_online == 1 && $collector->can_receive_orders == Collector::CAN_RECEIVE_ORDERS) {
            return $this->responseError(421, 'Collector already logged in another device!');
        }

        if ($collector->status == 0) {
            return $this->responseError(421, 'لايمكنك تسجيل الدخول من فضلك تواصل مع الأدارة!');
        }

        $data['type'] = 'online';
        $this->collectorRepository->setStatusLog($data, $collector);
        // save firebase token(device token)
        if ($request->deviceToken) {
            $collector->deviceToken()->create(['token' => $request->deviceToken]);
        }

        Event::dispatch('user.after.login', request('username'));

        // send notification to area manager
        $payload['model'] = auth($this->guard)->user();
        Event::dispatch('admin.alert.collector_sign_in', [auth($this->guard)->user(), $payload]);

        return $this->responseSuccess($this->createNewToken($collector), 'Success login!');
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request) {
        $data['type'] = 'offline';
        $collector = auth($this->guard)->user();

        // get all preparing orders for the collector
        $orders = Order::where([
                    'collector_id' => $collector->id,
                    'warehouse_id' => $collector->warehouse_id,
                    'status' => Order::STATUS_PREPARING,
        ]);

        if ($orders->count() > 0) {
            // check if there is another online collector but not the selected collector
            $onlineCollectors = Collector::where([
                        'is_online' => 1,
                        'status' => 1,
                        'can_receive_orders' => Collector::CAN_RECEIVE_ORDERS,
                        'warehouse_id' => $collector->warehouse_id,
                    ])->where('id', '!=', $collector->id)->first();

            if (!$onlineCollectors) {
                return $this->responseError(410, "يوجد طلبات ولا يوجد مجمعين اخرين برجاء انهاء الطلبات قبل تسجيل الخروج");
            } else {
                //update the orders with the online collector
                $orders->update(['collector_id' => $onlineCollectors->id]);
            }
        }
        $data['type'] = 'offline';
        $this->collectorRepository->setStatusLog($data, $collector);
        if ($request->deviceToken) {
            $collector->deviceToken()->where('token', $request->deviceToken)->delete();
        }

        // send notification to area manager
        $payload['model'] = auth($this->guard)->user();
        Event::dispatch('admin.alert.collector_sign_out', [auth($this->guard)->user(), $payload]);

        auth($this->guard)->logout();

        return response()->json(['message' => 'User successfully signed out']);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh() {

        return $this->responseSuccess([
                    'access_token' => auth()->refresh(),
                    'token_type' => 'bearer',
                    'expires_in' => (int) config('jwt.ttl'),
                    'user' => auth($this->guard)->user()
        ]);
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return array
     */
    protected function createNewToken($collector) {
        $token = JWTAuth::customClaims(['exp' => Carbon::now()->addYears(1)->timestamp])->fromUser($collector);
        $response = [
            'access_token' => $token,
            'token_type' => 'bearer',
            'user' => new CollectorSignle(auth($this->guard)->user()),
        ];
        return $response;
    }

}
