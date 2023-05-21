<?php

namespace Webkul\Admin\Repositories\Sales;

use App\Jobs\GetAndStoreDrivers;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Container\Container as App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Event;
use LaravelFCM\Message\Exceptions\InvalidOptionsException;
use Prettus\Validator\Exceptions\ValidatorException;
use Webkul\Category\Models\Category;
use Webkul\Collector\Models\Collector;
use Webkul\Collector\Models\CollectorLogLogin;
use Webkul\Core\Eloquent\Repository;
use Webkul\Core\Services\LocationService\Distance\DistanceService;
use Webkul\Core\Services\SendPushNotification;
use Webkul\Driver\Models\Driver;
use Webkul\Inventory\Models\Warehouse;
use Webkul\Product\Models\Product;
use Webkul\Sales\Contracts\Order;
use Webkul\Sales\Models\Order as OrderModel;
use Webkul\Sales\Repositories\OrderRepository as SalesRepository;
use Webkul\Sales\Services\NewOrderFilters\CheckItemsAvailableInAreaWarehouses;
use Webkul\Sales\Models\OrderViolation;

class OrderRepository extends Repository {

    /**
     * OrderItemRepository object
     *
     * @var SalesRepository
     */
    protected $salesRepository;

    /**
     * Create a new repository instance.
     *
     * @param SalesRepository $salesRepository
     * @param App $app
     */
    public function __construct(SalesRepository $salesRepository, App $app) {
        $this->salesRepository = $salesRepository;
        parent::__construct($app);
    }

    /**
     * Specify Model class name
     *
     * @return mixed
     */
    public function model() {
        return 'Webkul\Sales\Contracts\Order';
    }

    /**
     * @param $request
     * @param $status
     * @return mixed
     */
    public function list($request, $status) {
        $query = $this->newQuery();
        
        $query = $query->byArea();

        // handle sort option
        if ($request->has('sort') && !empty($request->sort)) {

            // handle multisort
            $sorts = explode(',', $request->sort);
            foreach ($sorts as $sort) {
                list($sortCol, $sortDir) = explode('|', $sort);
                $query = $query->orderBy($sortCol, $sortDir);
            }
        } else {
            $query = $query->orderBy('id', 'desc');
        }


        // Search by Area
        if ($request->exists('filter') && !empty($request['filter'])) {
            $query->where('increment_id', $request['filter'])
            ->orWhereHas('customer', function ($q) use ($request) {
                $q->where('name', 'LIKE', '%' . trim($request['filter']) . '%')
                  ->orWhere('phone', 'LIKE', '%' . trim($request['filter']) . '%');
            });
        }

        if ($request->exists('shipments') && !empty($request['shipments'])) {
            $shipments = $request['shipments'];
            $shipment_ids = explode(',', $shipments); 
            $shipment_ids = array_map(function($id) {
                return preg_replace('/[^0-9]/', '', $id) - 1000000; 
            }, $shipment_ids);
            $query->whereIn('shippment_id',$shipment_ids);
        }
        
        // Orders Satatus
        if (isset($status) && !empty($status)) {
            if ($status == 'pending') {
                $query = $query->whereIn('status', [OrderModel::STATUS_PENDING, OrderModel::STATUS_WAITING_CUSTOMER_RESPONSE]);
            } elseif ($status == 'active') {
                $query = $query->whereIn('status', [OrderModel::STATUS_PREPARING, OrderModel::STATUS_READY_TO_PICKUP, OrderModel::STATUS_ON_THE_WAY, OrderModel::STATUS_AT_PLACE]);
            } elseif ($status == 'history') {
                $query = $query->whereIn('status', [OrderModel::STATUS_DELIVERED, OrderModel::STATUS_CANCELLED, OrderModel::STATUS_CANCELLED_FOR_ITEMS, OrderModel::STATUS_RETURNED]);
            } elseif ($status == 'scheduled') {
                $query = $query->whereNotNull('customer_id')->where('status', OrderModel::STATUS_SCHEDULED)->orderBy('shippment_id', 'asc');
            } elseif ($status == 'pickup-orders') {
                $query = $query->whereNull('customer_id')->whereIn('status', [OrderModel::STATUS_SCHEDULED, OrderModel::STATUS_PREPARING, OrderModel::STATUS_READY_TO_PICKUP, OrderModel::STATUS_ON_THE_WAY, OrderModel::STATUS_AT_PLACE]);
            }
        }                
        // Search by Status
        if ($request->exists('status') && !empty($request['status'])) {
            $query->where('status', $request['status']);
        }

        // Search by Area
        if ($request->exists('area_id') && !empty($request['area_id'])) {
            $query->where('area_id', $request['area_id']);
        }

        // Search by Source
        if ($request->exists('channel_id') && !empty($request['channel_id'])) {
            $query->where('channel_id', $request['channel_id']);
        }

        // Search by Area
        if ($request->exists('driver_id') && !empty($request['driver_id'])) {
            $query->where('driver_id', $request['driver_id']);
        }

        // Search by Payment
        if ($request->exists('payment') && !empty($request['payment'])) {
            $query->whereHas('payment', function (Builder $query) use ($request) {
                $query->where('id', $request['payment']);
            });
        }

        if (isset($request['from_date']) && isset($request['to_date']) && !empty($request['from_date']) && !empty($request['to_date'])) {
            $query->whereBetween('created_at', [$request['from_date'] . ' 00:00:00', $request['to_date'] . ' 23:59:59']);
        }

        // Filter [ All | Flagged ]
        // Search by Area
        if ($request->exists('flagged') && !empty($request['flagged']) && $request['flagged'] == 'flagged') {
            $query->where('flagged', 1);
        }

        $perPage = $request->has('per_page') ? (int) $request->per_page : null;
        $pagination = $query->paginate($perPage);
        $pagination->appends([
            'sort' => $request->sort,
            'filter' => $request->filter,
            'per_page' => $request->per_page,
        ]);

        return $pagination;
    }

    /**
     * Show the specified order.
     *
     * @param int $id
     * @return mixed
     */
    public function orderDetails(int $id) {
        $order = $this->model->findOrFail($id);

        Event::dispatch('app-orders.show', $order);

        return $order;
    }

    /**
     *  Get Orders Count for each Status
     */
    public function ordersStatusCount() {

        $countStatusCollection = OrderModel::byArea()->selectRaw("
                                        COUNT(CASE WHEN `status` in ('" . OrderModel::STATUS_PENDING . "', '" . OrderModel::STATUS_WAITING_CUSTOMER_RESPONSE . "') THEN 1 END) AS 'pending_count',
                                        COUNT(CASE WHEN `status` in ('" . OrderModel::STATUS_PREPARING . "', '" . OrderModel::STATUS_READY_TO_PICKUP . "', '" . OrderModel::STATUS_ON_THE_WAY . "', '" . OrderModel::STATUS_AT_PLACE . "') THEN 1 END) AS 'active_count',
                                        COUNT(CASE WHEN customer_id is not null and `status` = '" . OrderModel::STATUS_SCHEDULED . "'  THEN 1 END) AS 'scheduled_count'
                                        ")->get();
                                        
        $countStatus = $countStatusCollection->map(function ($order) {
            return [
        'pending_count' => $order->pending_count,
        'active_count' => $order->active_count,
        'history_count' => '-',
        'scheduled_count' => $order->scheduled_count,
            ];
        });

        return $countStatus;
    }

    public function customerOrdersHistoryList($customer, $request) {

        $query = $this->newQuery();

        // handle sort option
        if ($request->has('sort') && !empty($request->sort)) {

            // handle multisort
            $sorts = explode(',', $request->sort);
            foreach ($sorts as $sort) {
                list($sortCol, $sortDir) = explode('|', $sort);
                $query = $query->orderBy($sortCol, $sortDir);
            }
        } else {
            $query = $query->orderBy('created_at', 'asc');
        }

        if ($customer && !empty($customer)) {
            $query->where('customer_id', '=', $customer->id);
        }
        if ($request->exists('id') && !empty($request['id'])) {
            $query->where('increment_id', '=', $request['id']);
        }


        if ($request->exists('start_date') && !empty($request['start_date']) && $request->exists('end_date') && !empty($request['end_date'])) {
            $query->where(function ($q) use ($request) {
                $dateFrom = $request['start_date'] . ' 00:00:00';
                $dateTo = $request['end_date'] . ' 23:59:59';
                $q->whereBetween('created_at', [$dateFrom, $dateTo]);
            });
        }

        $perPage = $request->has('per_page') ? (int) $request->per_page : null;

        $pagination = $query->paginate($perPage);
        $pagination->appends([
            'sort' => $request->sort,
            'filter' => $request->filter,
            'per_page' => $request->per_page
        ]);

        return $pagination;
    }

    public function callcenterReturnCustomerOrder($data) {
        $order = $this->find($data['order_id']);

        if ($order) {
            // update order items in qty_refunded (should add return qty to order items)
            $this->orderItemRefundedUpdate($order, $data);

            // send notification to operation manager
            // find the fatest driver to customer location
            $drivers = Driver::where('area_id', $order->area->id)->whereIn('availability', [Driver::AVAILABILITY_IDLE, Driver::AVAILABILITY_BACK, Driver::AVAILABILITY_ONLINE])->get();

            // Get Customer Address Location
            $customerAddress = $order->address;
            $locationData = [];
            foreach ($drivers as $driver) {
                // Get Driver Location from Cache (Redis)
                $driverData = Cache::get('driver_' . $driver->id);
                logOrderActionsInCache($order->id, "driver_lat_in_cache_is_{$driverData['lat']}");
                logOrderActionsInCache($order->id, "driver_long_in_cache_is_{$driverData['long']}");

                $driverWarehouse = $driver->warehouse;
                $diverObj['lat'] = $driverData['lat'] ? $driverData['lat'] : $driverWarehouse->longitude;
                $diverObj['long'] = $driverData['long'] ? $driverData['long'] : $driverWarehouse->longitude;
                // Prepare Driver Data
                $locationObj = [
                    'driver_id' => $driver->id,
                    'customer_id' => $order->customer_id,
                    'origins' => [
                        ['lat' => $customerAddress->latitude, 'long' => $customerAddress->longitude]
                    ],
                    'dsetinations' => [$diverObj]
                ];

                array_push($locationData, $locationObj);
            }

            // Finally, Calculate Distance among Drivers and Customer Address
            $distanceService = new DistanceService();
            $fatestDriver = $distanceService->getDistanceBetweenDriverToCustomer($locationData)[0];

            // send request notification to driver
            $dataToDriver = ['title' => 'Return Order Request', 'body' => 'Return Order Request', 'details' => ['order_id' => $order->id, 'key' => 'order_returned']];
            $this->sendNotificationToDriver($fatestDriver['driver_id'], $order, $dataToDriver);
        }

        return $order;
    }

    public function orderItemRefundedUpdate($order, $data) {
        // hold return item qty in cache
        Cache::put("order_{$order->id}_return", $data);

        $qtyReturned = 0;
        foreach ($data['items'] as $item) {
            $itemUpdate = $order->items()->where(['order_id' => $order->id, 'product_id' => $item['product_id']])->first();
            $qtyReturned = $qtyReturned + $itemUpdate->qty_returned;
            $itemUpdate->qty_returned = $item['qty'];
            $itemUpdate->return_reason = $item['return_reason'];
            $itemUpdate->save();
        }

        $order->items_qty_return = $qtyReturned;
        $order->save();
        return $order;
    }

    /**
     * @param $driverId
     * @param OrderModel $order
     * @param array $data
     * @return bool
     * @throws InvalidOptionsException
     */
    public function sendNotificationToDriver($driverId, OrderModel $order, array $data) {
        logOrderActionsInCache($order->id, 'start_send_notification_to_driver_return_request');

        Event::dispatch('app.order.return.send_notification_to_driver', $order);

        // Send Notification
        $driver = Driver::findOrFail($driverId);
        $tokens = $driver->deviceToken->pluck('token')->toArray();

        logOrderActionsInCache($order->id, 'notification_to_driver_return_request');

        return SendPushNotification::send($tokens, $data);
    }

    /**
     * @param $request
     * @return LengthAwarePaginator
     */
    public function searchForProducts($request) {
        $perPage = $request->has('per_page') ? (int) $request->per_page : null;

        $pagination =  Product::search(trim($request->text))->paginate($perPage);
        // $pagination =  Product::paginate($perPage);


        $pagination->appends([
            'per_page' => $request->per_page,
        ]);

        return $pagination;
    }


    public function allViolations($request) {
        $perPage = $request->has('per_page') ? (int) $request->per_page : null;

        $pagination =  OrderViolation::paginate($perPage);
        // $pagination =  Product::paginate($perPage);


        $pagination->appends([
            'per_page' => $request->per_page,
        ]);

        return $pagination;
    }

    /**
     * @param $id
     * @return mixed
     */
    public function cancelOrderForItems($id) {
        $order = $this->model->findOrFail($id);
        $order->status = OrderModel::STATUS_CANCELLED_FOR_ITEMS;
        $order->save();

        Event::dispatch('admin-orders.cance-for-items', $order);

        return $order;
    }

    /**
     * @param OrderModel $order
     * @param array $data
     * @return bool
     * @throws InvalidOptionsException
     */
    public function sendNotificationToCollector($warehouseId, OrderModel $order, array $data) {
        logOrderActionsInCache($order->id, 'start_send_notification_to_collector_return_request');
        Event::dispatch('app.return.order.return.send_notification_to_collector', $order);

        // Send Notification
        $collectors = Collector::with('deviceToken')->where('warehouse_id', $warehouseId)->where('availability', 'online')->get();
        $tokens = [];
        foreach ($collectors as $collector) {
            $tokens = array_merge($tokens, $collector->deviceToken->pluck('token')->toArray());
        }
        $data = [
            'title' => $data['title'],
            'body' => $data['body'],
            'data' => $data['details']
        ];
        logOrderActionsInCache($order->id, 'notification_to_collector_return_request');
        return SendPushNotification::send($tokens, $data);
    }

}
