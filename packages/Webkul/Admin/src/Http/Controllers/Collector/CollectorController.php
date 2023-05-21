<?php

namespace Webkul\Admin\Http\Controllers\Collector;

use Illuminate\Http\Request;
use Webkul\Sales\Models\Order;
use Illuminate\Support\Facades\Event;
use Webkul\Collector\Models\Collector;
use Webkul\Admin\Http\Resources\Collector\OrderAll;
use Webkul\Admin\Http\Resources\Collector\LogLoginAll;
use Webkul\Admin\Http\Resources\Sales\OrderViolations;
use Webkul\Admin\Http\Resources\Collector\CollectorAll;
use Webkul\Core\Http\Controllers\BackendBaseController;
use Webkul\Admin\Http\Requests\Collector\CollectorRequest;
use Webkul\Admin\Http\Resources\Collector\CollectorSignle;
use Webkul\Admin\Repositories\Collector\CollectorRepository;
use Webkul\Admin\Http\Resources\Collector\CollectorAvgPreparingTime;

class CollectorController extends BackendBaseController
{

    protected $collectorRepository;

    public function __construct(CollectorRepository $collectorRepository)
    {
        $this->collectorRepository = $collectorRepository;
    }

    public function list(Request $request)
    {
        $collectors = $this->collectorRepository->list($request);
        $data = new CollectorAll($collectors);
        return $this->responsePaginatedSuccess($data, null, $request);
    }

    public function show(Collector $collector)
    {
        $collector = new CollectorSignle($collector);
        return $this->responseSuccess($collector);
    }

    public function avgPreparingTime(Collector $collector)
    {
        $collector = new CollectorAvgPreparingTime($collector);
        return $this->responseSuccess($collector);
    }

    public function add(CollectorRequest $request)
    {

        $collector = $this->collectorRepository->create($request->all());

        Event::dispatch('admin.log.activity', ['create', 'collector', $collector, auth('admin')->user(), $collector]);

        Event::dispatch('admin.collector.created', $collector);

        return $this->responseSuccess($collector, 'New Collector has been created!');
    }

    public function update(Collector $collector, CollectorRequest $request)
    {
        $data = $request->all();
        $before = clone $collector;

        $collector = $this->collectorRepository->update($data, $collector->id);

        Event::dispatch('admin.log.activity', ['update', 'collector', $collector, auth('admin')->user(), $collector, $before]);

        return $this->responseSuccess(null, "Collector has been updated!");
    }

    public function setStatus(Collector $collector, Request $request)
    {

        $this->validate($request, [
            'status' => 'required|numeric|in:0,1',
        ]);
        $before = clone $collector;

        if ($request['status'] == 0) { // deactive
            $collector = $this->collectorRepository->update(['status' => $request['status'], 'is_online' => 0, 'availability' => 'offline'], $collector->id);
        } else {
            $collector = $this->collectorRepository->update($request->only('status'), $collector->id);
        }

        Event::dispatch('admin.collector.set-status', $collector);
        Event::dispatch('admin.log.activity', ['update-status', 'collector', $collector, auth('admin')->user(), $collector, $before]);

        return $this->responseSuccess();
    }

    public function setCanReceiveOrders(Collector $collector, Request $request)
    {

        $this->validate($request, [
            'can_receive_orders' => 'required|numeric|in:0,1',
        ]);
        $before = clone $collector;
        if ($request['can_receive_orders'] == 0) { // deactive
            $collector = $this->collectorRepository->update(['can_receive_orders' => Collector::CANNOT_RECEIVE_ORDERS], $collector->id);
        } else {
            $collector = $this->collectorRepository->update(['can_receive_orders' => Collector::CAN_RECEIVE_ORDERS], $collector->id);
        }

        Event::dispatch('admin.collector.set-can-receive-orders', $collector);
        Event::dispatch('admin.log.activity', ['update-can-receive-orders', 'collector', $collector, auth('admin')->user(), $collector, $before]);

        return $this->responseSuccess();
    }

    public function setLogout(Collector $collector, Request $request)
    {

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
                return $this->responseError(410, "There is no available collector in the warehouse.");
            } else {
                //update the orders with the online collector
                $orders->update(['collector_id' => $onlineCollectors->id]);
            }
        }
        ////////////////////////////////////////////////////////////////

        $before = clone $collector;

        $fieldsArray = ['is_online' => 0, 'availability' => Collector::AVAILABILITY_OFFLINE, 'can_receive_orders' => Collector::CANNOT_RECEIVE_ORDERS];

        $collector = $this->collectorRepository->update($fieldsArray, $collector->id);

        Event::dispatch('admin.collector.set-logout', $collector);
        Event::dispatch('admin.log.activity', ['set-logout', 'collector', $collector, auth('admin')->user(), $collector, $before]);

        return $this->responseSuccess();
    }

    public function logs($collectorId)
    {
        return $this->responsePaginatedSuccess(new LogLoginAll($this->collectorRepository->logs($collectorId)), null, request());
    }

    public function orders($collectorId)
    {
        $data = new OrderAll($this->collectorRepository->orders($collectorId, request()));
        return $this->responsePaginatedSuccess($data, null, request());
    }

    /**
     * @param Collector $collector
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function violations(Collector $collector, Request $request)
    {

        $data = new OrderViolations($collector->violations);

        return $this->responseSuccess($data);
    }
}