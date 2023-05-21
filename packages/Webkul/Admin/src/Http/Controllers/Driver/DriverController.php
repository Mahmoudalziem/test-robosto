<?php

namespace Webkul\Admin\Http\Controllers\Driver;

use Illuminate\Http\Request;
use Webkul\Driver\Models\Driver;
use Webkul\Driver\Events\MoneyAdded;
use Illuminate\Support\Facades\Event;
use Webkul\Collector\Models\Collector;
use Webkul\Admin\Http\Resources\Driver\OrderAll;
use Webkul\Admin\Http\Resources\Driver\DriverAll;
use Webkul\Admin\Http\Resources\Driver\LogBreakAll;
use Webkul\Admin\Http\Resources\Driver\LogLoginAll;
use Webkul\Admin\Http\Requests\Driver\DriverRequest;
use Webkul\Admin\Http\Resources\Driver\DriverSignle;
use Webkul\Admin\Repositories\Sales\OrderRepository;
use Webkul\Admin\Http\Resources\Driver\OrderLogSignle;
use Webkul\Admin\Http\Resources\Sales\OrderViolations;
use Webkul\Admin\Repositories\Driver\DriverRepository;
use Webkul\Admin\Http\Resources\Driver\LogEmergencyAll;
use Webkul\Core\Http\Controllers\BackendBaseController;
use Webkul\Admin\Http\Resources\Driver\OrderAllDelivered;
use Webkul\Admin\Http\Resources\Driver\DriverAvgDeliveryTime;
use Spatie\EventSourcing\StoredEvents\Models\EloquentStoredEvent;
use Webkul\Admin\Http\Resources\Driver\ordersDriverDispatchDispatchingAll;
use App\Jobs\RoboDistanceJob;
use Webkul\Sales\Models\Order;

class DriverController extends BackendBaseController
{

    protected $driverRepository;
    protected $orderRepository;

    public function __construct(DriverRepository $driverRepository, OrderRepository $orderRepository)
    {
        $this->driverRepository = $driverRepository;
        $this->orderRepository = $orderRepository;
    }

    public function list(Request $request)
    {

        $drivers = $this->driverRepository->list($request);
        $data = new DriverAll($drivers);
        return $this->responsePaginatedSuccess($data, null, $request);
    }

    public function listByArea(Request $request)
    {
        $drivers = $this->driverRepository->where('area_id', $request['area_id'])->get();
        $data = new DriverAll($drivers);
        return $this->responseSuccess($data);
    }

    public function show(Driver $driver)
    {
        $driver = new DriverSignle($driver);
        return $this->responseSuccess($driver);
    }

    public function avgDeliveryTime(Driver $driver)
    {
        $driver = new DriverAvgDeliveryTime($driver);
        return $this->responseSuccess($driver);
    }

    public function add(DriverRequest $request)
    {
        $driver = $this->driverRepository->create($request->all());

        Event::dispatch('admin.driver.created', $driver);
        Event::dispatch('admin.log.activity', ['create', 'driver', $driver, auth('admin')->user(), $driver]);

        return $this->responseSuccess($driver, 'New Dirver has been created!');
    }

    public function update(Driver $driver, DriverRequest $request)
    {
        $data = $request->all();
        $before = clone $driver;

        $driver = $this->driverRepository->update($data, $driver->id);

        Event::dispatch('admin.log.activity', ['update', 'driver', $driver, auth('admin')->user(), $driver, $before]);

        return $this->responseSuccess(null, "Driver has been updated!");
    }

    public function setStatus(Driver $driver, Request $request)
    {
        $this->validate($request, [
            'status' => 'required|numeric|in:0,1',
        ]);

        $before = clone $driver;

        $fieldsArray = $request['status'] == 0 ?
            ['status' => $request['status'], 'is_online' => 0, 'availability' => Driver::AVAILABILITY_OFFLINE, 'can_receive_orders' => Driver::CANNOT_RECEIVE_ORDERS]
            :
            ['status' => $request['status']];

        $fieldsArray['warehouse_id'] = $driver->warehouse_id;

        $driver = $this->driverRepository->update($fieldsArray, $driver->id);

        Event::dispatch('admin.driver.set-status', $driver);
        Event::dispatch('admin.log.activity', ['update-status', 'driver', $driver, auth('admin')->user(), $driver, $before]);

        return $this->responseSuccess();
    }

    public function setLogout(Driver $driver, Request $request)
    {

        $before = clone $driver;

        $fieldsArray =  ['is_online' => 0, 'availability' => Driver::AVAILABILITY_OFFLINE, 'can_receive_orders' => Driver::CANNOT_RECEIVE_ORDERS];
        $fieldsArray['warehouse_id'] = $driver->warehouse_id;
        $this->revokeReadyToPickUpOrdersFromDriver($driver);
        $driver = $this->driverRepository->update($fieldsArray, $driver->id);

        Event::dispatch('admin.driver.set-logout', $driver);
        Event::dispatch('admin.log.activity', ['set-logout', 'driver', $driver, auth('admin')->user(), $driver, $before]);

        return $this->responseSuccess();
    }

    public function logsEmergency($driverId)
    {

        $data = new LogEmergencyAll($this->driverRepository->logs('emergency', $driverId));
        return $this->responsePaginatedSuccess($data, null, request());
    }

    public function logsLogin($driverId)
    {

        $data = new LogLoginAll($this->driverRepository->logs('login', $driverId));
        return $this->responsePaginatedSuccess($data, null, request());
    }

    public function logsBreak($driverId)
    {

        $data = new LogBreakAll($this->driverRepository->logs('break', $driverId));
        return $this->responsePaginatedSuccess($data, null, request());
    }

    public function orders($driverId)
    {

        $orders = EloquentStoredEvent::query()
            ->whereEventClass(MoneyAdded::class)
            ->where('event_properties->driverId', $driverId)
            ->latest()->paginate();
        $data = new OrderAll($orders);
        return $this->responsePaginatedSuccess($data, null, request());
    }

    public function ordersDriverDispatching($driverId)
    {

        $data = new ordersDriverDispatchDispatchingAll($this->driverRepository->ordersDriverDispatching($driverId, request()));
        return $this->responsePaginatedSuccess($data, null, request());
    }

    public function orderDetail($orderId)
    {

        $data = new OrderLogSignle($this->orderRepository->findOrFail($orderId));
        return $this->responseSuccess($data);
    }


    public function supervisorRate(Driver $driver, Request $request)
    {
        $this->validate($request, [
            'rate' => 'required|numeric|min:1|max:10',
        ]);

        $data = $request->only('rate');

        if ($driver->supervisor_rate) {
            return $this->responseError(410, "There is already rate before at this month.");
        }
        $rate = $data['rate'] / config('robosto.DRIVER_SUPERVISOR_RATE_PER');

        $driver->supervisor_rate = $rate;
        $driver->save();

        $driver->supervisorRating()->create([
            'admin_id'  => auth('admin')->id(),
            'rate' => $rate
        ]);

        Event::dispatch('driver.supervisor-rating-bonus', $driver->id);

        return $this->responseSuccess();
    }


    /**
     * @param Driver $driver
     * @param Request $request
     * 
     * @return JsonResponse
     */
    public function violations(Driver $driver, Request $request)
    {

        $data = new OrderViolations($driver->violations);

        return $this->responseSuccess($data);
    }

    /**
     * @param Driver $driver
     *
     * @return void
     */
    private function revokeReadyToPickUpOrdersFromDriver(Driver $driver)
    {
        $readyToPickupOrders = Order::where('status', Order::STATUS_READY_TO_PICKUP)->where('driver_id', $driver->id)->get();
        $defaultDriver = Driver::where('area_id', $driver->area_id)->where('default_driver', Driver::DEFAULT_DRIVER)->first();
        if ($defaultDriver) {
            foreach ($readyToPickupOrders as $order) {

                $order->driver_id = $defaultDriver->id;
                $order->assigned_driver_id = $defaultDriver->id;
                $order->save();
                // Fire Robosto Distance Service
                RoboDistanceJob::dispatch($order);
            }
        }
    }
}
