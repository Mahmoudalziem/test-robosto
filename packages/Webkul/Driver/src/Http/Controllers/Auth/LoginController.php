<?php

namespace Webkul\Driver\Http\Controllers\Auth;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Webkul\User\Models\Role;
use App\Jobs\RoboDistanceJob;
use Webkul\Sales\Models\Order;
use Webkul\Driver\Models\Driver;
use Illuminate\Http\JsonResponse;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;
use App\Jobs\AssignNewOrdersToDriver;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Redis;
use Webkul\Driver\Models\DriverMotor;
use Webkul\Area\Models\AreaClosedHour;
use App\Exceptions\ResponseErrorException;
use Webkul\Driver\Repositories\DriverRepository;
use Webkul\Driver\Models\DriverTransactionRequest;
use Webkul\Driver\Http\Requests\DriverLoginRequest;
use Webkul\Driver\Http\Resources\Driver\DriverSingle;
use Webkul\Core\Http\Controllers\BackendBaseController;

class LoginController extends BackendBaseController
{

    /**
     * Contains current guard
     *
     * @var string
     */
    protected $guard;

    /**
     * Contains route related configuration
     *
     * @var array
     */
    protected $_config;
    protected $driverRepository;

    /**
     * Controller instance
     *
     * @param  \Webkul\Customer\Repositories\CustomerRepository  $customerRepository
     */
    public function __construct(DriverRepository $driverRepository)
    {
        $this->guard = 'driver';
        auth()->setDefaultDriver($this->guard);
        $this->driverRepository = $driverRepository;
    }

    /**
     * Method to store user's sign up form data to DB.
     *
     * @param DriverLoginRequest $request
     * @return JsonResponse
     */
    public function login(DriverLoginRequest $request)
    {

        $jwtToken = null;
        if (!$jwtToken = auth($this->guard)->attempt($request->only('username', 'password'))) {
            return $this->responseError(401, "الاسم او كلمة المرور غير صحيحه");
        }

        $driver = Driver::find(auth($this->guard)->id());

        $this->checkAreaClosingHours($driver);

        if ($driver->is_online == 1) {
            return $this->responseError(421, 'تم تسجيل الدخول من قبل عن طريق جهاز اخر');
        }

        // Default Driver Satuts
        $availability = Driver::AVAILABILITY_ONLINE;
        $canReciveOrder = Driver::CAN_RECEIVE_ORDERS;

        // Fetch Some Data about the driver
        $driverHasTransaction = $driver->transactions->where('status', DriverTransactionRequest::STATUS_PENDING);
        $currentOrder = $this->driverRepository->currentOrder($driver);

        // if the driver has a transaction
        if ($driverHasTransaction->isNotEmpty()) {
            $availability = Driver::AVAILABILITY_TRANSACTION;
            // $canReciveOrder = Driver::CANNOT_RECEIVE_ORDERS;
        }

        // if this driver has Order
        if ($currentOrder) {
            // the availability will be 'delivery'
            $driver->availability = Driver::AVAILABILITY_DELIVERY;

            if ($driver->multi_order == '0' || $driver->on_the_way) {
                $driver->can_receive_orders = Driver::CANNOT_RECEIVE_ORDERS;
            } else {
                Log::info('line:94 Login Function -> ' . $driver->id);
                $driver->can_receive_orders = Driver::CAN_RECEIVE_ORDERS;
            }

            $driver->save();
        } else {
            $driver->update(['availability' => $availability, 'can_receive_orders'  => $canReciveOrder]);
        }

        // Store Status Log
        $this->driverRepository->setStatusLogin($driver);

        // Save Login Logs
        $driver->loginLogs()->create(['action' => Driver::AVAILABILITY_ONLINE]);

        // Update is_online to 1
        $driver->update(['is_online' => 1]);

        $token = JWTAuth::customClaims(['exp' => Carbon::now()->addYears(1)->timestamp])->fromUser($driver);

        // save firebase token(device token)
        if ($request->deviceToken) {
            $driver->deviceToken()->create(['token' => $request->deviceToken]);
        }
        Event::dispatch('driver.after.login', $driver);

        // send notification to area manager
        $payload['model'] = auth($this->guard)->user();
        Event::dispatch('admin.alert.driver_sign_in', [auth($this->guard)->user(), $payload]);

        // Publish New Order Status
        $this->publishDriverToRedis($driver, Driver::AVAILABILITY_ONLINE);

        Log::info('From Login Function ==> Assign New Orders On The Driver -> ' . $driver->id);
        AssignNewOrdersToDriver::dispatch($driver);

        return $this->responseSuccess(['token' => $token, 'driver' => new DriverSingle($driver)]);
    }

    /**
     * @param Request $request
     *
     * @return [type]
     */
    public function logout(Request $request)
    {

        $driver = auth($this->guard)->user();

        // Default after Login
        $data['type'] = Driver::AVAILABILITY_OFFLINE;

        //check allowed driver logout  9 hrs
        $this->checkDriverAllowedLogoutDate($driver);

       // if this driver has Order, then the availability will be Delivery
        $this->checkDriverActiveOrders($driver);

        // Store Status Log
        $this->driverRepository->setStatusLog($data, $driver);

        // Save Login Logs
        $driver->loginLogs()->create(['action' => Driver::AVAILABILITY_OFFLINE]);

        // Update Driver to offline status
        $driver->update(['is_online' => 0, 'can_receive_orders' => Driver::CANNOT_RECEIVE_ORDERS]);

        if ($request->deviceToken) {
            $driver->deviceToken()->where('token', $request->deviceToken)->delete();
        }

        // Publish New Order Status
        $this->publishDriverToRedis($driver, Driver::AVAILABILITY_OFFLINE);

        // send notification to area manager
        $payload['model'] = auth($this->guard)->user();
        Event::dispatch('admin.alert.driver_sign_out', [auth($this->guard)->user(), $payload]);
        Event::dispatch('driver.working-hours-bonus', $driver->id);

        auth($this->guard)->logout();

        return response()->json(['message' => 'تم تسجيل الخروج بنجاح']);
    }

    /**
     * @param Driver $driver
     *
     * @return mixed
     */
    private function checkAreaClosingHours(Driver $driver)
    {
        Carbon::setLocale("en");

        $areaClosedHours = AreaClosedHour::where("area_id", $driver->area_id)
            ->where("from_day", Carbon::now()->dayName)
            ->where('from_hour', '<', Carbon::now()->format('H:i:s'))
            ->where('to_hour', '>=', Carbon::now()->format('H:i:s'))
            ->first();

        if ($areaClosedHours) {
            throw new ResponseErrorException(421, 'عذرا لايمكن تسجيل الدخول في الوقت الحالي');
        }
    }


    /**
     * @param Driver $driver
     *
     * @return mixed
     */
    private function checkDriverActiveOrders(Driver $driver)
    {
        $activeOrders = Order::whereIn('status', [Order::STATUS_READY_TO_PICKUP, Order::STATUS_ON_THE_WAY, Order::STATUS_AT_PLACE])->where('driver_id', $driver->id)->get();

        // Check At Place Orders
        if ($activeOrders->whereIn('status', [Order::STATUS_AT_PLACE])->count()) {
            throw new ResponseErrorException(421, 'عذرا لايمكن تسجيل الخروج في الوقت الحالي');
        }

        // If All Orders On the Way
        if ($activeOrders->whereIn('status', [Order::STATUS_ON_THE_WAY])->count() && $activeOrders->where('status', Order::STATUS_READY_TO_PICKUP)->count() == 0) {
            throw new ResponseErrorException(421, 'عذرا لايمكن تسجيل الخروج في الوقت الحالي');
        }

        // If Some Orders On the Way and Some Orders ReadyToPickup
        if ($activeOrders->whereIn('status', [Order::STATUS_READY_TO_PICKUP])->count()) {

            // If the driver has readyToPickup Orders, revoke them from the driver and assign them to the default driver.
            $this->revokeOrdersFromDriver($activeOrders, $driver);

        }
    }


    /**
     * @param Driver $driver
     *
     * @return mixed
     */
    private function checkDriverAllowedLogoutDate(Driver $driver)
    {
        // If All Logout time (current) less than AllowedLogoutDate
        if ($driver->allowed_logout_date && $driver->allowed_logout_date->gt(Carbon::now())) {
            throw new ResponseErrorException(421, 'عذرا لايمكن تسجيل الخروج قبل انتهاء فترة الدوام');
        }
    }


    /**
     * @param mixed $activeOrders
     * @param Driver $driver
     *
     * @return void
     */
    private function revokeOrdersFromDriver($activeOrders, Driver $driver)
    {
        $readyToPickupOrders = $activeOrders->where('status', Order::STATUS_READY_TO_PICKUP);

        $defaultDriver = Driver::where('area_id', $driver->area_id)->where('default_driver', Driver::DEFAULT_DRIVER)->first();

        foreach ($readyToPickupOrders as $order) {

            $order->driver_id = $defaultDriver->id;
            $order->assigned_driver_id = $defaultDriver->id;
            $order->save();

            // Fire Robosto Distance Service
            RoboDistanceJob::dispatch($order);
        }
    }

    /**
     * Publish New Order Status
     */
    private function publishDriverToRedis(Driver $driver, string $newStatus)
    {
        Redis::publish(
            'driver.order.status.updated',
            json_encode(
                [
                    'driver' => [
                        'id' => $driver->id,
                        'status' => $newStatus
                    ]
                ]
            )
        );
    }

    /**
     * Refresh a token.
     *
     * @return JsonResponse
     */
    public function refresh()
    {

        return $this->responseSuccess([
            'access_token' => auth()->refresh(),
            'token_type' => 'bearer',
            'expires_in' => (int) config('jwt.ttl'),
            'user' => auth($this->guard)->user()
        ]);
    }

    protected function createNewToken($collector)
    {
        $token = JWTAuth::customClaims(['exp' => Carbon::now()->addYears(1)->timestamp])->fromUser($collector);
        $response = [
            'access_token' => $token,
            'token_type' => 'bearer',
            'user' => new CollectorSignle(auth($this->guard)->user()),
        ];
        return $response;
    }
}