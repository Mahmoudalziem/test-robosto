<?php

namespace Webkul\Collector\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Webkul\Sales\Models\Order;
use App\Jobs\EndInventoryControl;
use Illuminate\Http\JsonResponse;
use Webkul\Product\Models\Product;
use App\Jobs\CustomerReturnedOrder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Jobs\ItemsReturnedToWarehouse;
use App\Jobs\ItemsReturnedToWarehouseLater;
use Webkul\Inventory\Models\InventoryControl;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Collector\Http\Resources\Task\TaskAll;
use Webkul\Collector\Http\Requests\ItemStockRequest;
use Webkul\Collector\Http\Resources\Task\TaskSingle;
use Prettus\Repository\Exceptions\RepositoryException;
use Webkul\Collector\Http\Resources\Order\OrderReturn;
use Webkul\Collector\Http\Resources\Order\OrderSingle;
use Webkul\Collector\Repositories\CollectorRepository;
use Webkul\Core\Http\Controllers\BackendBaseController;
use Webkul\Collector\Http\Requests\CollectorOrderRequest;
use Webkul\Collector\Http\Resources\Inventory\ProductAll;
use Webkul\Collector\Http\Resources\Order\OrderArchivedAll;
use Webkul\Collector\Http\Resources\Inventory\ProductSingle;
use Webkul\Collector\Http\Resources\Collector\CollectorSignle;
use Webkul\Collector\Http\Requests\CollectorOrderReturnedRequest;
use Webkul\Collector\Http\Resources\Inventory\InventoryProductSingle;
use Webkul\Admin\Repositories\Inventory\InventoryTranasctionRepository;
use Webkul\Collector\Http\Resources\Inventory\InventoryControlSingle;

class CollectorController extends BackendBaseController
{

    protected $collectorRepository;
    protected $orderRepository;
    protected $inventoryTranasctionRepository;

    public function __construct(
        CollectorRepository $collectorRepository,
        OrderRepository $orderRepository,
        InventoryTranasctionRepository $inventoryTranasctionRepository
    ) {
        $this->collectorRepository = $collectorRepository;
        $this->orderRepository = $orderRepository;
        $this->inventoryTranasctionRepository = $inventoryTranasctionRepository;
    }

    public function profile()
    {
        $profile = new CollectorSignle(auth()->user());
        return $this->responseSuccess($profile);
    }

    public function currentOrder()
    {
        $collector = auth('collector')->user();
        $order = null;
        if ($collector->can_receive_orders) {
            $warehouseId = $collector->warehouse_id;
            // get preparing order for collector(warehose of collector)
            $currentOrder = $this->collectorRepository->currentOrder($collector);
            // count orders and updated orders
            $updatedOrders = $this->listUpdatedOrders($warehouseId, $collector);
            $ordersCount = $currentOrder->count();
            $updatedOrdersCount = isset($updatedOrders) ? count($updatedOrders) : 0;
            $append['orders_count'] = $ordersCount + $updatedOrdersCount;

            $order = count($currentOrder) > 0 ? new OrderSingle($currentOrder->first(), $append) : null;
            // get current updated order if there's no orders exists
            if (!$order && count($updatedOrders) > 0) {
                $order = new OrderSingle($updatedOrders->first(), $append) ?? null;
            }
        }
        return $this->responseSuccess($order);
    }

    private function listUpdatedOrders($warehouseId, $collector)
    {

        if (Cache::has('warehouse_' . $warehouseId . '_updated_orders')) {
            return Order::where('status', Order::STATUS_PREPARING)
                ->where('collector_id', $collector->id)
                ->whereIn('id', Cache::get('warehouse_' . $warehouseId . '_updated_orders'))->get();
        } else {
            return [];
        }
    }

    private function removeUpdatedOrder($data)
    {
        if (Cache::has('warehouse_' . $data['warehouse_id'] . '_updated_orders')) {
            $updatedorderList = Cache::get('warehouse_' . $data['warehouse_id'] . '_updated_orders');
            if (($key = array_search($data['order_id'], $updatedorderList)) !== false) {
                unset($updatedorderList[$key]);
                Cache::put('warehouse_' . $data['warehouse_id'] . '_updated_orders', array_values($updatedorderList));
            }
        }
    }

    /**
     * @param CollectorOrderRequest $request
     *
     * @return mixed
     */
    public function orderById(CollectorOrderRequest $request)
    {
        $data = $request->only(['order_id']);
        // Get Order
        $append['orders_count'] = 0;
        $order = new OrderSingle($this->orderRepository->find($data['order_id']), $append);
        return $this->responseSuccess($order);
    }

    /**
     * @param CollectorOrderRequest $request
     *
     * @return mixed
     */
    public function orderReadyToPickup(CollectorOrderRequest $request)
    {

        $data = $request->only(['order_id']);
        $collector = auth('collector')->user();
        $data['collector_id'] = $collector->id;
        $data['warehouse_id'] = $collector->warehouse_id;

        // remove order from updated orders list after print
        $updatedOrders = $this->listUpdatedOrders($data['warehouse_id'], $collector);
        if ($updatedOrders && in_array($data['order_id'], $updatedOrders->pluck('id')->toArray())) {
            $this->removeUpdatedOrder($data);
            //return $this->responseSuccess($updatedOrders->first());
        }

        // Get Order
        $order = $this->orderRepository->find($data['order_id']);

        if ($order->status != Order::STATUS_PREPARING) {
            return $this->responseError();
        }

        // Call the function that Handle Collector Request
        $order = $this->orderRepository->collectorOrderReadyToPickup($order);

        return $this->responseSuccess($order);
    }

    public function archivedOrders()
    {
        $orders = $this->collectorRepository->archivedOrders(auth()->user()->warehouse->id);
        $data = new OrderArchivedAll($orders);
        return $this->responsePaginatedSuccess($data, null, request());
    }

    // Inventory
    // list inventory products
    public function inventoryProductsList()
    {
        // get collector warehost
        $collecotorWarehouse = auth()->user()->warehouse->id;
        // get all transfers for collector [from_warehouse_id || to_warehouse_id]
        $product = new ProductAll($this->collectorRepository->inventoryProductsList($collecotorWarehouse));
        return $this->responsePaginatedSuccess($product, null, request());
    }

    // inventory product show
    public function inventoryProductShow(Product $product)
    {
        $data = null;
        // get all transfers for collector [from_warehouse_id || to_warehouse_id]
        if ($this->collectorRepository->inventoryProductOne($product)) {
            $data = new InventoryProductSingle($this->collectorRepository->inventoryProductOne($product));
        }

        return $this->responseSuccess($data);
    }

    // list tasks
    public function tasks()
    {
        // get collector warehost
        $collecotorWarehouse = auth()->user()->warehouse->id;
        // get all transfers for collector [from_warehouse_id || to_warehouse_id]
        $tasks = new TaskAll($this->collectorRepository->tasks($collecotorWarehouse));
        return $this->responsePaginatedSuccess($tasks, null, request());
    }

    // task(transfer || adujstment) show
    public function show($id)
    {
        // get all transfers for collector [from_warehouse_id || to_warehouse_id]
        $tasks = new TaskSingle($this->collectorRepository->taskShow($id));
        return $this->responseSuccess($tasks);
    }

    // collector confirm inventory transaction (transfer ) by change status
    public function confirmTransaction($id, Request $request)
    {
        $data = $request->only('task_type');
        // transfer set status
        $transaction = $this->inventoryTranasctionRepository->findOrFail($id);
        // Collector confirm that items will transfer OUT (on the way)
        if (auth()->user()->warehouse_id == $transaction->from_warehouse_id) {
            $this->collectorRepository->transactionSetStatusOnTheWay($transaction, $data['task_type']);
        }
        // Collector confirm that items will transfer OUT
        if (auth()->user()->warehouse_id == $transaction->to_warehouse_id) {
            $this->collectorRepository->transactionSetStatusTransfered($transaction, $data['task_type']);
        }
        return $this->responseSuccess(null, "Status Changed Successfully!");
    }

    /**
     * @param CollectorOrderReturnedRequest $request
     * @return JsonResponse
     * @throws RepositoryException
     */
    public function confirmReturnedOrderReceived(CollectorOrderReturnedRequest $request)
    {
        $data = $request->only(['order_id']);

        // Get Order
        $order = $this->orderRepository->find($data['order_id']);

        if ($order->status != Order::STATUS_CANCELLED) {
            return $this->responseError();
        }

        // Run Job
        CustomerReturnedOrder::dispatch($order);

        return $this->responseSuccess();
    }

    /**
     * @param CollectorOrderReturnedRequest $request
     * @return JsonResponse
     * @throws RepositoryException
     */
    public function confirmReturnedItemsReceived(CollectorOrderReturnedRequest $request)
    {
        $data = $request->only(['order_id', 'items']);
        $data['collector_id'] = auth('collector')->id();

        // Get Order
        $order = $this->orderRepository->find($data['order_id']);

        if ($order->status != Order::STATUS_DELIVERED) {
            return $this->responseError();
        }

        // Run Job
        ItemsReturnedToWarehouse::dispatch($order, $data['items']);

        return $this->responseSuccess();
    }

    /**
     * @param CollectorOrderReturnedRequest $request
     * @return JsonResponse
     * @throws RepositoryException
     */
    public function confirmReturnedItemsReceivedOneHoure(Request $request)
    {
        $data = $request->only(['order_id', 'items']);
        $data['collector_id'] = auth('collector')->id();
        $adjustment['warehouse_id'] = auth('collector')->user()->warehouse_id;
        $adjustment['status'] = 2; // pending
        $data['adjustmentData'] = $adjustment;
        // Get Order
        $order = $this->orderRepository->find($data['order_id']);

        if ($order->status != Order::STATUS_DELIVERED) {
            return $this->responseError();
        }

        $inventoryAdjustment = $this->collectorRepository->newReturnAdjustment($order, $data);

        // Run Job
        ItemsReturnedToWarehouseLater::dispatch($order, $inventoryAdjustment, $data['items'])->delay(Carbon::now()->addHour());

        return $this->responseSuccess();
    }

    /**
     * Show the specified order.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function collectorReturnOrderResponse(Request $request)
    {
        $order_id = $request->order_id;
        $data['driver_id'] = auth('driver')->id();
        $order = $this->orderRepository->find($order_id);
        $orderCache = Cache::get("order_{$order_id}_return");

        if ($order) {
            $response = new OrderReturn($order, $orderCache);
        }

        return $this->responseSuccess($response);
    }

    public function startInventoryControl(Request $request)
    {

        $data['collector_id'] = auth('collector')->id();
        $data['warehouse_id'] = auth('collector')->user()->warehouse_id;
        $data['area_id'] = auth('collector')->user()->area_id;
        $data['start_date'] = now();

        $inventoryControl = InventoryControl::where('area_id', $data['area_id'])
            ->where('warehouse_id', $data['warehouse_id'])
            ->where('is_active', 1)
            ->where('is_completed', 0)
            ->first();

        if ($inventoryControl) {
            return $this->responseError(404, "There is an open inventory control available");
        }

        $inventoryControl = InventoryControl::create($data);

        return $this->responseSuccess(new InventoryControlSingle($inventoryControl));
    }

    public function checkInventoryControl(Request $request)
    {

        $data['collector_id'] = auth('collector')->id();
        $data['warehouse_id'] = auth('collector')->user()->warehouse_id;
        $data['area_id'] = auth('collector')->user()->area_id;

        $inventoryControl = InventoryControl::where('area_id', $data['area_id'])
            ->where('warehouse_id', $data['warehouse_id'])
            ->where('is_active', 1)
            ->where('is_completed', 0)
            ->first();

        if (!$inventoryControl) {
            return $this->responseError(404, "There is no inventory control available");
        }

        return $this->responseSuccess(new InventoryControlSingle($inventoryControl));
    }

    public function scanItem(Request $request)
    {
        $data['barcode'] = $request->barcode;
        $scanedItem = Product::where('barcode', $data['barcode'])->first();

        if ($scanedItem) {
            return $this->responseSuccess(new ProductSingle($scanedItem));
        }

        return $this->responseError();
    }

    public function postItemStock(ItemStockRequest $request)
    {

        $data['collector_id'] = auth('collector')->id();
        $data['area_id'] = auth('collector')->user()->area_id;
        $data['warehouse_id'] = auth('collector')->user()->warehouse_id;
        $data['product_id'] = $request->product_id;
        $data['qty_stock'] = $request->qty_stock;

        $inventoryControl = InventoryControl::where('area_id', $data['area_id'])
            ->where('warehouse_id', $data['warehouse_id'])
            ->where('is_active', 1)
            ->where('is_completed', 0)
            ->first();

        if (!$inventoryControl) {
            return $this->responseError(404, "There is no active inventory control!");
        }

        $productStock = $this->collectorRepository->postItemStock($data);

        return $this->responseSuccess();
    }

    public function endInventoryControl(Request $request)
    {

        $data['collector_id'] = auth('collector')->id();
        $data['warehouse_id'] = auth('collector')->user()->warehouse_id;
        $data['area_id'] = auth('collector')->user()->area_id;
        $data['end_date'] = now();

        $inventoryControl = InventoryControl::where('area_id', $data['area_id'])
            ->where('warehouse_id', $data['warehouse_id'])
            ->where('is_active', 1)
            ->first();
        if (!$inventoryControl) {
            return $this->responseError(404, "There is no active inventory control!");
        }
        $data['inventory_control'] = $inventoryControl;
        $data['is_completed'] = 1;
        $data['is_active'] = 0;
        $inventoryControl->update($data); // close the inventory control

        EndInventoryControl::dispatch($data);
        return $this->responseSuccess($inventoryControl);
    }
}