<?php

namespace Webkul\Driver\Http\Controllers;

use App\Jobs\CallDriverJob;
use Illuminate\Http\Request;
use Webkul\User\Models\Role;
use App\Jobs\RoboDistanceJob;
use App\Jobs\ShippmentOrderRouter;
use Webkul\User\Models\Admin;
use Webkul\Motor\Models\Motor;
use Webkul\Sales\Models\Order;
use Webkul\Driver\Models\Driver;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Webkul\Core\Services\FileUpload;
use Webkul\Driver\Events\MoneyAdded;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Redis;
use Webkul\Inventory\Models\Warehouse;
use Webkul\Driver\Http\Resources\OrderAll;
use Webkul\Driver\Http\Resources\OrderReturn;
use Webkul\Driver\Http\Resources\OrderSingle;
use Webkul\Core\Services\SendPushNotification;
use Webkul\Driver\Http\Resources\WalletOrders;
use Webkul\Sales\Repositories\OrderRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Webkul\Driver\Repositories\DriverRepository;
use Webkul\Driver\Http\Resources\OrderHistoryAll;
use Webkul\Driver\Models\DriverTransactionRequest;
use Webkul\Customer\Http\Controllers\Auth\SMSTrait;
use Webkul\Driver\Http\Requests\DriverOrderRequest;
use Webkul\Driver\Http\Resources\OrderAfterUpdated;
use Webkul\Driver\Http\Requests\DriverWalletRequest;
use Webkul\Driver\Http\Resources\Driver\DriverSingle;
use Prettus\Repository\Exceptions\RepositoryException;
use Webkul\Driver\Http\Requests\DriverNewOrderRequest;
use Webkul\Driver\Http\Transformers\DriverTransformer;
use Webkul\Core\Http\Controllers\BackendBaseController;
use Webkul\Driver\Http\Requests\DriverEmergencyRequest;
use Webkul\Driver\Http\Requests\DriverStatusLogRequest;
use Webkul\Driver\Http\Resources\Order as OrderResource;
use LaravelFCM\Message\Exceptions\InvalidOptionsException;
use Webkul\Driver\Http\Requests\CustomerUpdatedOrderRequest;
use Webkul\Driver\Http\Requests\DriverOrderDeliveredRequest;
use Spatie\EventSourcing\StoredEvents\Models\EloquentStoredEvent;
use Webkul\Driver\Http\Requests\CompleteDeliverDriverWalletRequest;
use Webkul\Promotion\Models\Promotion;

class DriverController extends BackendBaseController {

    use SMSTrait;

    /**
     * Contains current guard
     *
     * @var array
     */
    protected $guard;

    /**
     * OrderRepository object
     *
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var DriverRepository
     */
    private $driverRepository;

    public function __construct(DriverRepository $driverRepository, OrderRepository $orderRepository) {
        $this->guard = 'driver';
        auth()->setDefaultDriver($this->guard);
        $this->driverRepository = $driverRepository;
        $this->orderRepository = $orderRepository;
    }

    public function profile() {
        $driver = Auth::user();

        return $this->responseSuccess(new DriverSingle($driver));
    }

    public function motorLog(Request $request) {
        $data = $request->only('motor_id', 'image', 'motor_condition');
        $driver = Auth::user();

        // motor condition image
        //protected function saveImgBase64($data, $model, $type = 'image', $createThumb = false);
        $this->driverRepository->motorLog($data, $driver);

        return responder()->success($driver, DriverTransformer::class)->respond();
    }

    /**
     * Get Motors
     */
    public function getMotors(Request $request) {
        $motors = Motor::all();

        return $this->responseSuccess($motors);
    }

    public function setStatusLog(DriverStatusLogRequest $request) {

        $data = $request->only('type', 'duration', 'order_id');
        $driver = Auth::user();

        $setStatus = $this->driverRepository->setStatusLog($data, $driver);

        return $this->responseSuccess();
    }

    public function requestBreak(DriverStatusLogRequest $request) {
        $data = $request->only('duration');
        $data['type'] = 'break';
        $driver = Auth::user();
        $requestBreak = $this->driverRepository->requestBreak($data, $driver);

        // send notification to area manager
        $payload['model'] = auth($this->guard)->user();
        $payload['duration'] = $request['duration'];
        Event::dispatch('admin.alert.driver_request_break', [auth($this->guard)->user(), $payload]);

        return $this->responseSuccess();
    }

    public function confirmAtWarehouse(Request $request) {
        $driver = Auth::user();
        $this->driverRepository->confirmAtWarehouse($driver);
        return $this->responseSuccess();
    }

    /**
     * @param DriverEmergencyRequest $request
     * @return JsonResponse
     * @throws RepositoryException
     */
    public function requestEmergency(DriverEmergencyRequest $request) {

        $data = $request->only('order_id', 'reason');
        $driver = Auth::user();

        $this->driverRepository->driverRequestEmergency($data, $driver);

        return $this->responseSuccess();
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function driverWallet(Request $request) {
        $driver = Auth::user();
        $orders = $driver->orders()->where('status', Order::STATUS_DELIVERED)->whereDate('created_at', date('Y-m-d'));

        $perPage = $request->has('per_page') ? (int) $request->per_page : null;
        $pagination = $orders->paginate($perPage);
        $pagination->appends([
            'per_page' => $request->per_page,
        ]);

        $lastTransaction = $driver->transactions->where('status', DriverTransactionRequest::STATUS_PENDING)->first();

        $data['orders'] = new WalletOrders($pagination);
        $data['amount_in_wallet'] = $driver->wallet;
        $data['last_transaction'] = $lastTransaction ? $lastTransaction->id : null;

        return $this->customResponsePaginatedSuccess($data, $request);
    }

    /**
     * @param $data
     * @param null $message
     * @param $request
     * @return JsonResponse
     */
    protected function customResponsePaginatedSuccess($data, $request, $message = null) {
        $response = null;
        if ($data['orders']->resource instanceof LengthAwarePaginator) {
            $response = $data['orders']->toResponse($request)->getData();
        }

        $response->amount_in_wallet = $data['amount_in_wallet'];
        $response->last_transaction = $data['last_transaction'];
        $response->status = 200;
        $response->success = true;
        $response->message = $message;

        return response()->json($response);
    }

    /**
     * @param DriverWalletRequest $request
     * @return JsonResponse
     */
    public function deliverMoney(DriverWalletRequest $request) {
        $dataGiving = $request->only(['otp']);
        Log::info($dataGiving);
        $driver = Auth::user();
        $amount = $request->amount;

        // the driver cannot make request if his wallet less than given amount
        // OR -> has PENDING transaction
        if ($driver->wallet < $amount || $driver->transactions->where('status', DriverTransactionRequest::STATUS_PENDING)->first()) {
            return $this->responseError();
        }

        // Make Driver Transaction Request
        $transaction = $driver->transactions()->create([
            'area_id' => $driver->area_id,
            'warehouse_id' => $driver->warehouse_id,
            'amount' => $amount,
            'current_wallet' => $driver->wallet - $amount
        ]);

        // Make Driver Offline until process is complete
        $driver->availability = Driver::AVAILABILITY_TRANSACTION;
        // $driver->can_receive_orders = Driver::CANNOT_RECEIVE_ORDERS;
        $driver->save();

        // Make Temoprary function to accept transaction Request
        if (!isset($dataGiving['otp'])) {
            $this->acceptDriverTransactionRequest($transaction, $driver);
        }

        // Send Transaction Notification To Area Manager
        $this->sendTransactionNotificationToAreaManager($driver, $amount);

        // Return Orders again
        $orders = $driver->orders()->whereDate('created_at', date('Y-m-d'));

        $perPage = $request->has('per_page') ? (int) $request->per_page : null;
        $pagination = $orders->paginate($perPage);
        $pagination->appends([
            'per_page' => $request->per_page,
        ]);

        $lastTransaction = $driver->transactions->where('status', DriverTransactionRequest::STATUS_PENDING)->first();
        $data['orders'] = new WalletOrders($pagination);
        $data['amount_in_wallet'] = $this->driverRepository->findOrFail($driver->id)->wallet;
        $data['last_transaction'] = $lastTransaction ? $lastTransaction->id : null;

        return $this->customResponsePaginatedSuccess($data, $request);
    }

    /**
     * @param DriverWalletRequest $request
     * @return JsonResponse
     */
    public function completeTransactionRequest(CompleteDeliverDriverWalletRequest $request) {
        $driver = Auth::user();
        $otp = $request->otp;

        // Get Driver Transaction Request
        $transaction = $driver->transactions->where('status', DriverTransactionRequest::STATUS_PENDING)->first();

        if (!$transaction) {
            return $this->responseError(422, 'No Pending Transaction Exists');
        }

        // if that the given otp not valid
        if ($transaction->otp != $otp) {
            return $this->responseError(422, 'Otp is not correct');
        }

        // if all passed, then accept Transaction
        $transaction->status = DriverTransactionRequest::STATUS_RECEIVED;
        $transaction->save();

        // Make Driver Online again
        $driver->availability = Driver::AVAILABILITY_IDLE;
        $driver->save();

        // Subtract amount from Driver wallet
        $driver->subtractMoney($transaction->amount);

        // Add Money to Area Wallet
        $driver->area->addMoney($transaction->amount, $driver->id, $transaction->admin_id);

        // Add Money to AreaManager Wallet
        $transaction->admin->areaManagerAddMoney($transaction->amount, $transaction->area_id, $driver->id);

        return $this->responseSuccess();
    }

    /**
     * @param DriverWalletRequest $request
     * @return JsonResponse
     */
    public function cancelTransactionRequest() {
        $driver = Auth::user();

        // Get Driver Transaction Request
        $transaction = $driver->transactions->where('status', DriverTransactionRequest::STATUS_PENDING)->first();

        if (!$transaction) {
            return $this->responseError(422, 'No Pending Transaction Exists');
        }

        // if all passed, then cancel Transaction
        $transaction->status = DriverTransactionRequest::STATUS_CANCELLED;
        $transaction->save();

        // Make Driver Online again
        $driver->availability = Driver::AVAILABILITY_IDLE;
        $driver->save();

        return $this->responseSuccess();
    }

    /**
     * Accept Driver Transaction Request
     *
     * @param mixed $transaction
     * @param mixed $driver
     *
     * @return mixed
     */
    private function acceptDriverTransactionRequest($transaction, $driver) {
        $adminAreas = $driver->area->admins;

        $role = Role::where('slug', Role::AREA_MANAGER)->first();

        $admin = DB::table('admin_roles')->where('role_id', $role->id)->whereIn('admin_id', $adminAreas->pluck('id')->toArray())->first();
        if ($admin) {
            $admin = Admin::find($admin->admin_id);
        } else {
            $admin = Admin::first();
        }

        // Subtract amount from Driver wallet
        $driver->subtractMoney($transaction->amount);

        // Add Money to Area Wallet
        $driver->area->addMoney($transaction->amount, $driver->id, $transaction->admin_id);

        // Add Money to AreaManager Wallet
        $admin->areaManagerAddMoney($transaction->amount, $transaction->area_id, $driver->id);

        // Update Transaction Status
        $transaction->status = DriverTransactionRequest::STATUS_RECEIVED;
        $transaction->admin_id = $admin->id;
        $transaction->save();

        // Make Driver Offline until process is complete
        $driver->availability = Driver::AVAILABILITY_ONLINE;
        $driver->save();

        return true;
    }

    /**
     * Show the specified order.
     *
     * @param DriverNewOrderRequest $request
     * @return JsonResponse
     */
    public function driverNewOrderResponse(DriverNewOrderRequest $request) {
        $data = $request->only(['action', 'order_id', 'reason']);
        $data['driver_id'] = auth('driver')->id();

        // Get Order
        $order = $this->orderRepository->findOrFail($data['order_id']);
        $driver = auth('driver')->user();

        // in case, there is new warehouse but there is no collector added to this warehouse
        if (!count(Warehouse::find($driver->warehouse_id)->collectors)) {
            return $this->responseError(410, 'عفواً لايمكنك قبول هذا الطلب');
        }

        if ($order->status != Order::STATUS_PENDING) {
            return $this->responseError(410, 'عفواً لايمكنك قبول هذا الطلب');
        }

        if ($driver->availability == Driver::AVAILABILITY_DELIVERY && $driver->multi_order == 1 && $driver->on_the_way == true) {
            return $this->responseError(410, 'عفواً لايمكنك قبول هذا الطلب');
        }

        // Call the function that Handle Driver Response
        $order = $this->orderRepository->driverNewOrderResponse($order, $driver, $data);

        $response = null;
        if ($order && $data['action'] == 'confirm') {
            $response = new OrderResource($order);
            Redis::publish(
                    'driver.order.status.updated',
                    json_encode(
                            [
                                'driver' => ['id' => $driver->id, 'status' => Driver::AVAILABILITY_DELIVERY],
                                'order' => ['id' => $order->id]
                            ]
                    )
            );
        }

        // check if this driver has many orders
        $activeOrders = $driver->activeOrders;
        if ($order && $activeOrders->isNotEmpty() && count($activeOrders->where('id', '!=', $order->id))) {
            return $this->responseError(200, "شكراً , تم قبول الطلب");
        }

        return $this->responseSuccess($response);
    }

    /**
     * @param Order $order
     */
    private function publishOrderEventToRedis(Order $order) {
        $redisData = [
            'order_id' => $order->id,
            'customer' => [
                'id' => $order->customer_id,
                'name' => $order->customer->name,
                'avatar' => $order->customer->avatar_url,
                'tokens' => $order->customer->deviceToken->pluck('token')->toArray()
            ],
            'driver' => [
                'id' => $order->driver_id,
                'name' => $order->driver ? $order->driver->name : null,
                'avatar' => $order->driver ? $order->driver->image_url : null,
                'tokens' => $order->driver ? $order->driver->deviceToken->pluck('token')->toArray() : null
            ]
        ];

        Redis::publish(
                'driver.received.order',
                json_encode(
                        [
                            'driver' => ['id' => $order->driver_id, 'status' => Driver::AVAILABILITY_DELIVERY],
                            'order' => ['id' => $order->id]
                        ]
                )
        );
        Redis::publish('open.channel', json_encode($redisData));
    }

    /**
     * Show the specified order.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     * @throws RepositoryException
     */
    public function driverReturnOrderResponse(Request $request) {
        $order_id = $request->order_id;
        $data['driver_id'] = auth('driver')->id();
        $order = $this->orderRepository->find($order_id);
        $orderCache = Cache::get("order_{$order_id}_return");

        if ($order) {
            $response = new OrderReturn($order, $orderCache);
        }

        return $this->responseSuccess($response);
    }

    /**
     * Show the specified order.
     *
     * @param DriverOrderRequest $request
     * @return JsonResponse
     * @throws InvalidOptionsException
     */
    public function driverConfirmReceivingItems(DriverOrderRequest $request) {
        $data = $request->only(['order_id']);
        $data['driver_id'] = auth('driver')->id();

        // Get Order
        $order = $this->orderRepository->findOrFail($data['order_id']);

        if ($order->status != Order::STATUS_READY_TO_PICKUP) {
            return $this->responseError();
        }

        // Call the function that Handle Driver Response
        $order = $this->orderRepository->driverConfirmReceivingItems($data);

        $response = null;
        if ($order) {
            $response = new OrderResource($order);
            // Publish Event to Redis
            if($order->customer_id){
                $this->publishOrderEventToRedis($order);
            }
        }

        return $this->responseSuccess($response);
    }

    /**
     * Show the specified order.
     *
     * @param DriverOrderRequest $request
     * @return JsonResponse
     * @throws InvalidOptionsException
     */
    public function driverConfirmReceivingReturnItemsFromCustomer(DriverOrderRequest $request) {
        $data = $request->only(['order_id']);
        $data['driver'] = auth('driver')->user();

        return $order = $this->orderRepository->driverConfirmReceivingReturnItemsFromCustomer($data);
    }

    /**
     * Show the specified order.
     *
     * @param DriverOrderRequest $request
     * @return JsonResponse
     * @throws InvalidOptionsException
     */
    public function driverOrderAtPlace(DriverOrderRequest $request) {
        $data = $request->only(['order_id']);
        $data['driver_id'] = auth('driver')->id();

        $order = $this->orderRepository->findOrFail($data['order_id']);

        if ($order->status != Order::STATUS_ON_THE_WAY) {
            return $this->responseError();
        }

        // Call the function that Handle Driver Response
        $order = $this->orderRepository->driverOrderAtPlace($data);

        $response = null;
        if ($order) {

            $response = new OrderResource($order);
        }

        CallDriverJob::dispatch(auth('driver')->id(), $order->id, CallDriverJob::ORDER_AT_PLACE_TYPE)->delay(now()->addMinutes(config('robosto.CALL_DRIVER_ORDER_AT_PLACE')));

        return $this->responseSuccess($response);
    }

    /**
     * Show the specified order.
     *
     * @param DriverOrderDeliveredRequest $request
     * @return JsonResponse
     * @throws RepositoryException
     */
    public function driverOrderDelivered(DriverOrderDeliveredRequest $request) {
        $data = $request->only(['order_id', 'amount_collected']);
        $data['driver_id'] = auth('driver')->id();

        // Get Order
        $order = $this->orderRepository->findOrFail($data['order_id']);
        if ($order->status != Order::STATUS_AT_PLACE) {
            return $this->responseError(407, 'Order has been delivered');
        }

        // Check Collected Amount that Paid by the customer
        $checkAmountCollected = $this->orderRepository->checkCollectedAmount($order, $data['amount_collected']);

        if ($checkAmountCollected['status'] == 'not_allowed') {
            return $this->responseError(406, 'Amount Collected Not Allowed', ['status' => 'not_allowed']);
        }

        // Make Order Delivered
        $this->orderRepository->driverOrderDelivered($order);
        if($order->customer){
           // Tel the Customer with Paid Money
          $this->notifyTheCustomerWithPayment($order, $data['amount_collected']);
        }

        // Save Order Delivered Date
        $this->saveOrderDeliveredDate(auth('driver')->user());

        // Publish New Order Status
        $this->publishDriverToRedis($order->driver, Driver::AVAILABILITY_BACK);

        // add cashback for specific promotion to customer wallet
        $this->cashBackToCustomerFromPromotion($order);
        if($order->shippment_id){
            ShippmentOrderRouter::dispatch($order);
        }
        return $this->responseSuccess();
    }

    /**
     * @param Order $order
     * @param mixed $amountCollected
     *
     * @return [type]
     */
    private function notifyTheCustomerWithPayment(Order $order, $amountCollected) {
        $customer = $order->customer()->first();
        $name = explode(' ', $customer->name)[0];

        // Send Notification to Customer
        $lang = request()->header('lang') ? request()->header('lang') : 'en';

        $msg = "Thanks, {$name} for your {$amountCollected} EGP payment. Please rate your experience with us!";
        $text = "Thanks, {$name} for your {$amountCollected} EGP payment. Your current balance is {$customer->wallet} EGP. Please rate your experience with us! ";
        $textWithoutPaid = "Thanks, {$name} for your order. Your current balance is {$customer->wallet} EGP. Please rate your experience with us! ";
        ;

        if ($lang == 'ar') {
            $msg = "شكراً {$name} لدفعك مبلغ {$amountCollected} جنية... قيم تجربتك معنا الأن! شكراً لك.";
            $text = "شكراً {$name} لدفعك مبلغ {$amountCollected} جنية. رصيدك الأن في روبستو {$customer->wallet} جنية.. قيم تجربتك معنا الأن!";
            $textWithoutPaid = "شكراً {$name} على الطلب. رصيدك الأن في روبستو {$customer->wallet} جنية.. قيم تجربتك معنا الأن!";
        }

        if ($amountCollected == 0) {
            $text = $textWithoutPaid;
        }

        $dataToCustomer = ['title' => 'Order Status', 'body' => $msg, 'details' => ['key' => 'order_delivered']];
        $this->orderRepository->sendNotificationToCustomer($order, $dataToCustomer);

        // Send SMS if Customer Wallet Updated
        if ($order->customer_balance != 0) {
            $this->sendSMS($customer->phone, $text);
        }
    }

    /**
     * @param Driver $driver
     *
     * @return void
     */
    private function saveOrderDeliveredDate(Driver $driver) {
        $driver->last_order_date = now();
        $driver->save();
    }

    /**
     * Show the specified order.
     *
     * @param DriverOrderRequest $request
     * @return JsonResponse
     * @throws RepositoryException
     */
    public function customerReturnedOrder(DriverOrderRequest $request) {
        $data = $request->only(['order_id', 'reason']);
        $data['driver_id'] = auth('driver')->id();

        $this->driverRepository->customerReturnedOrder($data);

        return $this->responseSuccess();
    }

    /**
     * Show the specified order.
     *
     * @param CustomerUpdatedOrderRequest $request
     * @return JsonResponse
     * @throws RepositoryException
     * @throws InvalidOptionsException
     */
    public function customerUpdatedOrder(CustomerUpdatedOrderRequest $request) {
        $data = $request->only(['order_id', 'items']);

        $order = $this->driverRepository->customerUpdatedOrder($data);

        $order = new OrderAfterUpdated($order);

        return $this->responseSuccess($order);
    }

    /**
     * Show the specified order.
     *
     * @param DriverOrderRequest $request
     * @return JsonResponse
     * @throws RepositoryException
     * @throws InvalidOptionsException
     */
    public function reachedToWarehouse(DriverOrderRequest $request) {
        $data = $request->only(['order_id']);
        $data['driver_id'] = auth('driver')->id();

        // Send Notification To Collector
        $this->driverRepository->reachedToWarehouseWithReturnedOrder($data);

        return $this->responseSuccess();
    }

    public function ordersHistory() {
        $orders = $this->driverRepository->ordersHistory(Auth::user());
        $data = new OrderHistoryAll($orders);
        return $this->responseSuccess($data);
    }

    public function prioritizeOnTheWayOrder(DriverOrderRequest $request) {
        $data = $request->only(['order_id']);
        $data['driver_id'] = auth('driver')->id();

        // Get Order
        $order = $this->orderRepository->findOrFail($data['order_id']);

        if ($order->status != Order::STATUS_ON_THE_WAY) {
            return $this->responseError();
        }
        // Call the function that Handle Driver Response
        $order = $this->orderRepository->driverConfirmReceivingItems($data);
        Cache::put("driver_{$data['driver_id']}_prioritize_order", $order->id);
        return $this->responseSuccess();
    }

    public function currentOrder() {
        $driver = auth('driver')->user();
        $order = null;

        // Get Driver Current Order
        $currentOrder = $this->driverRepository->currentOrder($driver);
        if ($currentOrder) {
            $order = new OrderSingle($currentOrder);
            Log::info('ORDER');
            Log::info($order);
            // Cache the current Order
            $this->saveCurrentOrderInCache($order, $driver);
        }
        return $this->responseSuccess($order);
    }

    /**
     * @param Order $order
     * @param Driver $driver
     *
     * @return bool
     */
    private function saveCurrentOrderInCache(OrderSingle $order, Driver $driver) {
        Cache::put("driver_{$driver->id}_current_order", $order->id);

        // Save Order for the driver
        $activeOrders = Cache::get("current_active_orders");

        if ($activeOrders) {

            if (isset($activeOrders[$driver->id])) {
                $activeOrders[$driver->id] = $order->id;
                Cache::put("current_active_orders", $activeOrders);
            } else {
                $activeOrders[$driver->id] = $order->id;
                Cache::put("current_active_orders", $activeOrders);
            }
            return true;
        }

        Cache::put("current_active_orders", [$driver->id => $order->id]);
    }

    public function activeOrders(Request $request) {
        $driver = auth('driver')->user();

        $activeOrders = $this->driverRepository->activeOrders($driver);
        $order = count($activeOrders) > 0 ? new OrderAll($activeOrders) : null;

        return $this->responseSuccess($order);
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function startDelivery(Request $request) {
        $driver = auth('driver')->user();
        Log::info("Driver " . $driver->id . " Started delivery");

        if ($driver->can_receive_orders == Driver::CANNOT_RECEIVE_ORDERS) {
            return $this->responseError(406, "انت بالفعل على الطريق. نتمنى لك السلامة");
        }

        $activeOrders = $this->orderRepository->findWhereIn('status', [Order::STATUS_ON_THE_WAY, Order::STATUS_READY_TO_PICKUP])->where('driver_id', $driver->id);

        // Check On The Way Orders
        if ($activeOrders->where('status', Order::STATUS_ON_THE_WAY)->count() == 0) {
            return $this->responseError(406, "لايمكنك التحرك الان");
        }

        // If the driver has readyToPickup Orders, revoke them from the driver and assign them to the default driver.
        if ($activeOrders->where('status', Order::STATUS_READY_TO_PICKUP)->count()) {

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

        // Change Driver to cannot receive orders
        $this->driverRepository->startDelivery($driver);
        Log::info("Fire Event => " . $driver->id);
        Event::dispatch('driver.start-delivery', [$driver->id]);

        return $this->responseSuccess();
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function incentives(Request $request) {
        $driver = auth('driver')->user();

        $data = ['amount' => (float) $driver->incentive];

        return $this->responseSuccess($data);
    }

    /**
     * Publish New Order Status
     */
    private function publishDriverToRedis(Driver $driver, string $newStatus) {
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
     * Publish New Order Status
     */
    private function cashBackToCustomerFromPromotion(Order $order) {

        if ($order->promotion_id) {
            $cashbackPromotionCollection = collect(config('robosto.PROMOTOION_CASH_BACK'));
            $cashbackPromotion = $cashbackPromotionCollection->where('promo_code_id', $order->promotion_id)->first();
            if ($cashbackPromotion) {
                Log::info(["cashbackPromotion" => $cashbackPromotion]);
                // add cachback to customer wallet
                $order->customer->addMoneyFromPromotionCashback($cashbackPromotion['amount'], $order->id, $order->promotion_id);

                // send SMS to Customer to tell him he received cashback
                $this->notifyTheCustomerWithCashback($order, $cashbackPromotion['amount']);
            }
        }
    }

    private function notifyTheCustomerWithCashback(Order $order, $amountCollected) {
        $customer = $order->customer()->first();
        $name = explode(' ', $customer->name)[0];

        // Send Notification to Customer


        $msg = "مبروك دلوقتي معاك  {$amountCollected} ج كاش باك .";
        $text = "مبروك دلوقتي معاك {$amountCollected} ج في محفظتك، تقدر تطلب الي نفسك فيه.";

        $dataToCustomer = ['title' => 'Cashback', 'body' => $msg, 'details' => ['key' => 'cashback_delivered']];
        $this->orderRepository->sendNotificationToCustomer($order, $dataToCustomer);

        // Send SMS if Customer Wallet Updated
        if ($customer->wallet != 0) {
            $this->sendSMS($customer->phone, $text);
        }
    }

    /**
     * Handle the event.
     *
     * @param Driver $driver
     * @param float $amount
     * @return bool
     * @throws InvalidOptionsException
     *
     * @return [type]
     */
    private function sendTransactionNotificationToAreaManager(Driver $driver, float $amount = null) {
        $areaManagers = Admin::whereHas('areas', function ($q) use ($driver) {
                    $q->where('areas.id', $driver->area_id);
                })->whereHas('roles', function ($q) {
                    $q->where('roles.slug', Role::AREA_MANAGER);
                })->get();

        $tokens = [];
        foreach ($areaManagers as $admin) {
            $tokens = array_merge($tokens, $admin->deviceToken->pluck('token')->toArray());
        }

        $data = [
            'title' => "New Transaction from Driver {$driver->name}",
            'body' => "Driver {$driver->name} wants to give you {$amount} EGP",
            'data' => [
                'key' => 'new_driver_transaction'
            ]
        ];

        return SendPushNotification::send($tokens, $data);
    }

}
