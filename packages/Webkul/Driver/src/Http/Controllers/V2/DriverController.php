<?php


namespace Webkul\Driver\Http\Controllers\V2;

use Illuminate\Http\Request;
use Webkul\Sales\Models\Order;
use Webkul\Driver\Models\Driver;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Webkul\Driver\Http\Resources\OrderAll;
use Webkul\Driver\Http\Resources\OrderSingle;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Driver\Repositories\DriverRepository;
use Webkul\Driver\Http\Resources\OrderHistoryAll;
use Webkul\Customer\Http\Controllers\Auth\SMSTrait;
use Webkul\Core\Http\Controllers\BackendBaseController;
use Illuminate\Support\Facades\Log;

class DriverController extends BackendBaseController
{

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

    public function __construct(DriverRepository $driverRepository, OrderRepository $orderRepository)
    {
        $this->guard = 'driver';
        auth()->setDefaultDriver($this->guard);
        $this->driverRepository = $driverRepository;
        $this->orderRepository = $orderRepository;
    }

    public function currentOrder()
    {
        $driver = auth('driver')->user();
        $order = null;

        // Get Driver Current Order
        $currentOrder = $this->driverRepository->currentOrderV2($driver);
        Log::info('current order for driver '.$driver->id);
        if ($currentOrder) {
            Log::info('order id'.$currentOrder->id);
            $order = new OrderSingle($currentOrder);

            // Cache the current Order
            $this->saveCurrentOrderInCache($order, $driver);
        }else {
             Log::info('order id'.'no orders');
        }
        return $this->responseSuccess($order);
    }

    public function activeOrders()
    {
        $driver = auth('driver')->user();

        $activeOrders = $this->driverRepository->activeOrdersV2($driver);
        Log::info('active orders for driver '.$driver->id);
        Log::info(count($activeOrders));
        $order = count($activeOrders) > 0 ? new OrderAll($activeOrders) : null;

        return $this->responseSuccess($order);
    }

    /**
     * @param Order $order
     * @param Driver $driver
     *
     * @return bool
     */
    private function saveCurrentOrderInCache(OrderSingle $order, Driver $driver)
    {
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

}
