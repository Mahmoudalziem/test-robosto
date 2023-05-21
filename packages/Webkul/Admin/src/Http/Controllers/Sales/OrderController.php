<?php

namespace Webkul\Admin\Http\Controllers\Sales;

use Exception;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Webkul\User\Models\Role;
use App\Jobs\CheckOrderItems;
use App\Jobs\RoboDistanceJob;
use Illuminate\Http\Response;
use Webkul\Sales\Models\Order;
use Webkul\Core\Models\Channel;
use App\Jobs\GetAndStoreDrivers;
use function DeepCopy\deep_copy;
use Webkul\Driver\Models\Driver;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Webkul\Product\Models\Product;
use Illuminate\Support\Facades\Log;
use App\Jobs\CustomerCancelledOrder;
use Webkul\Customer\Models\Customer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Webkul\Collector\Models\Collector;
use Webkul\Promotion\Models\Promotion;
use App\Jobs\AcceptOrderByDefaultDriver;
use Webkul\Sales\Models\OldOrderItemSku;
use Webkul\Sales\Models\OrderLogsActual;
use Illuminate\Database\Eloquent\Builder;
use App\Exceptions\ResponseErrorException;
use Webkul\Inventory\Models\InventoryArea;
use \Webkul\Sales\Http\Traits\ItemsHandler;
use Webkul\Customer\Models\CustomerAddress;
use Illuminate\Database\Eloquent\Collection;
use Webkul\Sales\Models\Order as OrderModel;
use Illuminate\Validation\ValidationException;
use Webkul\Sales\Http\Traits\PrepareOrderData;
use Webkul\Sales\Http\Traits\WhiteFridayOffer;
use Illuminate\Pagination\LengthAwarePaginator;
use Webkul\Admin\Http\Resources\Sales\OrderAll;
use Webkul\Inventory\Models\InventoryWarehouse;
use App\Exceptions\PromotionValidationException;
use App\Exceptions\PlaceOrderValidationException;
use Webkul\Admin\Http\Requests\Sales\OrderRequest;
use Webkul\Admin\Http\Resources\Sales\OnlineDrivers;
use Webkul\Admin\Http\Resources\Sales\OrderNotesAll;
use Webkul\Admin\Repositories\Sales\OrderRepository;
use Webkul\Admin\Http\Resources\Sales\ProductsSearch;
use \Webkul\Sales\Repositories\OrderCommentRepository;
use Webkul\Admin\Http\Requests\Sales\CheckItemRequest;
use Webkul\Admin\Http\Requests\Sales\OrderNoteRequest;
use Webkul\Promotion\Repositories\PromotionRepository;
use Illuminate\Support\Collection as SupportCollection;
use Webkul\Core\Http\Controllers\BackendBaseController;
use Webkul\Admin\Http\Requests\Sales\UpdateOrderRequest;
use Webkul\Admin\Http\Requests\Sales\OrderComplaintRequest;
use Webkul\Admin\Http\Resources\Sales\ReOrderDetailsSingle;
use Webkul\Promotion\Services\ApplyPromotion\ApplyPromotion;
use Webkul\Admin\Http\Resources\Sales\Order as OrderResource;
use Webkul\Promotion\Services\PromotionValidation\CheckPromotion;
use Webkul\Sales\Repositories\OrderRepository as SalesRepository;
use App\Exceptions\CallcenterPlaceOrderTimeOutValidationException;
use App\Jobs\DispatchShippment;
use App\Jobs\ShippmentOrderRouter;
use App\Jobs\TrackingScheduledOrders;
use Webkul\Admin\Http\Requests\Sales\OrderViolationRequest;
use Webkul\Admin\Http\Resources\Sales\OnlineCollectors;
use Webkul\Admin\Http\Resources\Sales\OrderViolations;
use Webkul\Admin\Http\Resources\Sales\OrderItemWallet;
use Webkul\Promotion\Services\PromotionValidation\Rules\Available;
use Webkul\Promotion\Services\PromotionValidation\Rules\ValidDate;
use Webkul\Sales\Services\NewOrderFilters\CheckItemsAvailableInArea;
use Webkul\Promotion\Services\PromotionValidation\Rules\CustomerArea;
use Webkul\Promotion\Services\PromotionValidation\Rules\CustomerTags;
use Webkul\Promotion\Services\PromotionValidation\Rules\VouchersCount;
use Webkul\Promotion\Services\PromotionValidation\Rules\MaxItemQtyAllowed;
use Webkul\Promotion\Services\PromotionValidation\Rules\RedeemsAllowed;
use Webkul\Sales\Services\PlaceOrderValidation\CheckPlaceOrderIsAllowed;
use Webkul\Sales\Services\PlaceOrderValidation\Rules\CheckItemsInAreaRule;
use Webkul\Sales\Services\PlaceOrderValidation\Rules\CheckScheduleTimeRule;
use Webkul\Sales\Services\NewOrderFilters\CheckItemsAvailableInAreaWarehouses;
use Webkul\Sales\Services\PlaceOrderValidation\Rules\CheckItemsInWarehouseRule;
use Webkul\Sales\Services\PlaceOrderValidation\Rules\CheckManyOrdersAtTimeRule;
use Webkul\Promotion\Services\PromotionValidation\Rules\RedeemsAllowedWithOrder;
use Webkul\Sales\Services\PlaceOrderValidation\Rules\CheckCallCenterTimeOutRule;
use Webkul\Promotion\Services\PromotionValidation\Rules\MinimumOrderRequirements;
use Webkul\Sales\Models\OrderViolation;
use Webkul\Shipping\Models\Shippment;

class OrderController extends BackendBaseController {

    use PrepareOrderData,
        WhiteFridayOffer,
        ItemsHandler;

    /**
     * OrderRepository object
     *
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * SalesRepository object
     *
     * @var SalesRepository
     */
    protected $salesRepository;

    /**
     * PromotionRepository object
     *
     * @var PromotionRepository
     */
    protected $promotionRepository;

    protected $guard = 'admin';

    /**
     * Create a new controller instance.
     *
     * @param OrderRepository $orderRepository
     * @param SalesRepository $salesRepository
     * @param PromotionRepository $promotionRepository
     */
    public function __construct(OrderRepository $orderRepository, SalesRepository $salesRepository, PromotionRepository $promotionRepository) {
        $this->orderRepository = $orderRepository;
        $this->salesRepository = $salesRepository;
        $this->promotionRepository = $promotionRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @param null $status
     * @return JsonResponse
     */
    public function index(Request $request, $status = null) {
        // Get All Orders based on status
        $orders = $this->orderRepository->list($request, $status);
        $data['orders'] = new OrderAll($orders);
        $data['orders_status_count'] = $this->orderRepository->ordersStatusCount();
        return $this->customResponsePaginatedSuccess($data, null, $request);
    }

    /**
     * @param CheckItemRequest $request
     *
     * @return mixed
     */
    public function checkItems(CheckItemRequest $request) {
        $data = $this->prepareCheckItemsData($request);
        Log::info('here 1');
        $callCenter = auth($this->guard)->id();
        $customer = Customer::find($data['customer_id']);
        $this->checkFreeShippingCoupon($data);
        Log::info('here 2');

        // deattach bundle items and merge them to merged itmes
        $data['merged_items'] = $this->getMergedItems($data['items']);
        Log::info('here 3');

        // 1- First of All Check Items Available in Area
        $this->checkItemsAvailableInArea($data);
        Log::info('here 4');

        // 2- Check Promotion Validation
        $this->checkPromotionValidation($data, $customer);
        Log::info('here 5');

        $updating = false;
        $items = $this->prepareItemsForChecking($data['merged_items']);
        if (isset($data['order_id']) && !empty($data['order_id']) && count($data['merged_items']) == 0) {
            $updating = true;
        }

        $checkItemsAvailableInWarehouses = new CheckItemsAvailableInAreaWarehouses($items, $data['area_id'], $updating);
        $allWarehousesHaveItems = $checkItemsAvailableInWarehouses->getAllWarehousesHaveItems();
        Log::info('here 6');

        // if all items are available in one warehouse
        if ($allWarehousesHaveItems['items_found']) {

            // handle reponse if all items exist in one warehouse
            $response = $this->itemsExistInOneWarehouse($data, $callCenter);

            // Check if that this order can be updated or NOT, if the new price more than the current price we cannot update
            $this->checkIfOrderPaidAndCC($data, $response);

            return $this->responseSuccess($response, 'all items found');
        } else {

            // Get Highest warehouse that have items
            $warehouseHaveItems = $checkItemsAvailableInWarehouses->handleWarehouseWithHighestItems($allWarehousesHaveItems['warehouses']);

            // if no items in the whole area
            if (is_null($warehouseHaveItems['warehouse_id'])) {
                return $this->responseError(411, 'There are no these items in the area', null);
            }
            $order = null;
            if (isset($date['order_id'])) {
                $order = $this->orderRepository->find($data['order_id']);
            }
            $bundles = $this->checkItemsExistsInBundle($order, $warehouseHaveItems, $request);
            Log::info(['$bundles: ' => $bundles]);
            if (count($bundles)) {

                // get items in bundle not enough (available qty)
                // get items in bundle out of stock
                // get all availble updated items that can use for this order
                // compare bundle count in stock(total in stock= real) with availble qty in that can make the most bundle count
                $outOfStockItems = $this->buildBunldeOutOfStock($order, $warehouseHaveItems, $request);
                $cleanBunldeNotEnough = $this->cleanBunldeNotEnough($outOfStockItems, $order, $warehouseHaveItems, $request);

                // calculate bundle items
                $warehouseHaveItems = $this->reBuildItemsAfterUpdatedIfMutltiBundle($order, $cleanBunldeNotEnough, $request);
            }

            // Enhance Response to handle Items and Calculate Payment
            $response = $this->prepareItemsForResponse($data, $warehouseHaveItems['items']);

            return $this->responseError(410, null, $response);
        }

        return $this->responseSuccess(null, 'all items not found');
    }

    /**
     * @param Request $request
     *
     * @return array
     */
    private function prepareCheckItemsData(Request $request) {
        $data = $request->only(['area_id', 'customer_id', 'promo_code', 'items', 'order_id']);

        // Pass Data by reference to handle shadow area
        $this->getShadowArea($data);

        $data['old_items'] = $data['items'];
        if (isset($data['order_id']) && !empty($data['order_id'])) {
            $order = $this->salesRepository->find($data['order_id']);
            if($order->paid_type == OrderModel::PAID_TYPE_BNPL){
                $data['old_items'] = $this->addMarginToItems($data['old_items']);
                $data['items'] = $this->addMarginToItems($data['items']);
            }
            $data['customer_id'] = $order->customer_id;
            $data['area_id'] = $order->area_id;
            // Prepare Difference in Quantity between given items and order items
            $data = $this->prepareExistOrderItems($order, $data);
        }

        return $data;
    }

    /**
     * @param array $data
     * @param array $response
     *
     * @return mixed
     */
    public function checkIfOrderPaidAndCC(array $data, array $response) {
        if (isset($data['order_id']) && !empty($data['order_id'])) {
            $order = $this->salesRepository->find($data['order_id']);

            if ($order->is_paid == OrderModel::ORDER_PAID && $order->paid_type == OrderModel::PAID_TYPE_CC) {

                if ($response['payment_summary']['amount_to_pay'] > $order->final_total) {
                    throw new ResponseErrorException(410, 'Cannot Update the order with this amount which more than one order total');
                }
            }
        }
    }

    /**
     * Prepare Difference in Quantity between given items and order items
     *
     * @param OrderModel $order
     * @param array $data
     *
     * @return array
     */
    private function prepareExistOrderItems(OrderModel $order, array $data) {
        foreach ($order->items as $item) {
            foreach ($data['items'] as $key => $givenItem) {

                if ($item->product_id == $givenItem['id']) {
                    if ($givenItem['qty'] > $item->qty_shipped) {
                        $data['items'][$key]['qty'] = $givenItem['qty'] - $item->qty_shipped;
                    } else {
                        if (!$this->checkOrderWaitingResponse($order, $givenItem)) {
                            unset($data['items'][$key]);
                        }
                    }
                }
            }
        }

        return $data;
    }

    /**
     * @param OrderModel $order
     * @param array $item
     *
     * @return bool
     */
    public function checkOrderWaitingResponse(OrderModel $order, array $item) {
        if ($order->status == OrderModel::STATUS_WAITING_CUSTOMER_RESPONSE) {
            $itemsFromCache = Cache::get("order_{$order->id}_has_changes_in_items");
            foreach ($itemsFromCache['items']['not_enough'] as $key => $value) {
                if ($value['product_id'] == $item['id']) {
                    return true;
                }
            }

            foreach ($itemsFromCache['items']['out_of_stock'] as $key => $value) {
                if ($value['product_id'] == $item['id']) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param array $data
     * @param int $callCenter
     *
     * @return array
     */
    private function itemsExistInOneWarehouse(array $data, int $callCenter) {
        // Store Time to disable place order button through these seconds
        $cacheKey = "callcenter_{$callCenter}_place_order_for_customer_{$data['customer_id']}_after";
        $availableSeconds = config('robosto.CALLCENTER_DISABLE_BUTTON');
        $timeToFinish = now()->addSeconds($availableSeconds);

        // Cache Time out to Place New Order
        Cache::forget($cacheKey);
        Cache::add($cacheKey, $timeToFinish);

        // Handle Payment Summary
        $data['items'] = $data['old_items'];
        $cartItems = $this->handleValidItemsForCart($data['items']);

        // Data Required to calculate payment summary
        $data = [
            'items' => $cartItems,
            'customer' => Customer::find($data['customer_id']),
            'promo_code' => $data['promo_code'] ?? null,
        ];

        // Calculate Payment Summary
        $paymentSummary = $this->paymentSummary($data);

        return [
            'available_seconds' => $availableSeconds,
            'payment_summary' => $paymentSummary,
        ];
    }

    /**
     * Create New Order.
     *
     * @param OrderRequest $request
     * @param SalesRepository $salesRepository
     * @return JsonResponse
     * @throws Exception
     */
    public function create(OrderRequest $request) {
        $data = $request->only(['customer_id', 'promo_code', 'address_id', 'payment_method_id', 'note', 'items', 'scheduled_at', 'assigned_driver_id']);
        if($data["payment_method_id"] == 3 ){
            throw new PlaceOrderValidationException(410, 'change payment method');
        }
        $data = $this->prepareOrderData($data, 'PORTAL');
        // Apply White Friday Offer
        $data = $this->applyWhiteFridayOffer($data);

        $customer = Customer::find($data['customer_id']);

        $this->checkFreeShippingCoupon($data);

        // deattach bundle items and merge them to merged itmes
        $data['merged_items'] = $this->getMergedItems($data['items']);
        Log::info($data['items'], $data['merged_items']);

        // Define Order Validation Rules
        $this->checkPlaceOrderValidation($data, $customer);

        // 2- Check Promotion Validation
        $this->checkPromotionValidation($data, $customer);

        $items = $this->prepareItemsForChecking($data['merged_items']);

        $checkItemsAvailableInWarehouses = new CheckItemsAvailableInAreaWarehouses($items, $data['area_id']);
        $allWarehousesHaveItems = $checkItemsAvailableInWarehouses->getAllWarehousesHaveItems();

        // if all items are available in one warehouse, Place the Order
        if ($allWarehousesHaveItems['items_found']) {
            Log::info("Order Items Are Found");

            $warehousesHaveItems = $allWarehousesHaveItems['warehouses'];

            // Update Inventory Area before create the Order
            $this->decreaseInventoryArea($data['merged_items'], $data['area_id']);

            // In Case all checks Passed, then Create the Order
            $order = $this->salesRepository->create($data);

            // in case, order scheduled or Not
            if (isset($data['scheduled_at']) && !empty($data['scheduled_at']) && $data['scheduled_at'] != 0) {

                logOrderActionsInCache($order->id, 'order_scheduled');

                $order->scheduled_at = Carbon::createFromTimestamp($data['scheduled_at'])->format('Y-m-d H:i:s');
                $order->status = OrderModel::STATUS_SCHEDULED;
                $order->save();

                return $this->responseSuccess();
            }

            // Cache Warehouses that have Items
            Cache::forget("order_{$order->id}_warehouses_have_items");
            Cache::add("order_{$order->id}_warehouses_have_items", $warehousesHaveItems);

            // Accept Order Automatically By Default Driver
            Log::info("Firee Accept Order By Default Driver Job");
            Log::info(["OrderID" => $order->id]);
            Log::info(["warehouse" => $warehousesHaveItems]);
            AcceptOrderByDefaultDriver::dispatch($order, $warehousesHaveItems);

            // Run Job to handle Drivers after response
            // GetAndStoreDrivers::dispatch($order, $warehousesHaveItems);

            Event::dispatch('admin.log.activity', ['create', 'order', $order, auth($this->guard)->user(), $order]);

            return $this->responseSuccess();
        } else {

            // Get Highest warehouse that have items
            $warehouseHaveItems = $checkItemsAvailableInWarehouses->handleWarehouseWithHighestItems($allWarehousesHaveItems['warehouses']);

            // if no items in the whole area
            if (is_null($warehouseHaveItems['warehouse_id'])) {
                return $this->responseError(411, 'There are no these items in the area', null);
            }

            // Enhance Response to handle Items and Calculate Payment
            $response = $this->prepareItemsForResponse($data, $warehouseHaveItems['items']);

            return $this->responseError(410, null, $response);
        }

        return $this->responseError();
    }

    /**
     * @param array $data
     * @param Customer $customer
     *
     * @return mixed
     */
    private function checkPlaceOrderValidation(array $data, Customer $customer) {
        $rule = new CheckCallCenterTimeOutRule($data);
        $rule->setNext(new CheckItemsInAreaRule($customer, $data['area_id']))
                ->setNext(new CheckScheduleTimeRule($data));

        // 1- Start Order Validation Chaining
        $checkPlaceOrder = new CheckPlaceOrderIsAllowed($data['merged_items']);
        $checkPlaceOrder->setRule($rule);
        $checkPlaceOrder->checkPlaceOrderIsAllowed();
    }

    /**
     * @param array $data
     *
     * @return mixed
     */
    private function checkItemsAvailableInArea(array $data) {
        $checkItemsInArea = new CheckItemsAvailableInArea($data['merged_items'], $data['area_id']);
        $checkItems = $checkItemsInArea->checkProductInInventory();
        Log::info('items checking');
        Log::info($checkItems);
        $checkedItems = ['out_of_stock' => $checkItems['outOfStockItems']];

        $response = $this->prepareItemsForResponse($data, $checkedItems, true);

        if (count($checkItems['outOfStockItems']) != 0) {
            throw new PlaceOrderValidationException(410, __('sales::app.itemsNotAvailable'), $response);
        }
    }

    /**
     * @param array $data
     *
     * @return bool
     */
    public function checkPromotionValidation(array $data, Customer $customer) {

        if (isset($data['promo_code']) && !empty($data['promo_code'])) {

            // First of all, Check that this is not the first order for the customer
            if ($customer->invitation_applied == 0 && !is_null($customer->invited_by)) {
                throw new PromotionValidationException(422, __('customer::app.notValidPromoCode'));
            }

            // Get the Promotion
            $promotion = $this->promotionRepository->findOneByField('promo_code', $data['promo_code']);
            // implement extra promotion rules
            if (config('robosto.EXTERA_PROMOTOION_RULES')) {
                $promotion = $this->extraPromotionRules($promotion);
            }

            if (isset($data['order_id']) && !empty($data['order_id'])) {
                $order = $this->salesRepository->find($data['order_id']);
                $promoRedeemsRule = new RedeemsAllowedWithOrder($customer, $order);
                $voucherRule = new VouchersCount($order);
            } else {
                $promoRedeemsRule = new RedeemsAllowed($customer);
                $voucherRule = new VouchersCount();
            }

            // Define Validation Rules
            $rule = new Available();
            $rule->setNext(new ValidDate())
                    ->setNext($voucherRule)
                    ->setNext(new MaxItemQtyAllowed($data['old_items']))
                    ->setNext(new MinimumOrderRequirements($this->totalItemPrice($data['old_items'], $promotion), $this->sumItemsQty($data['old_items'])))
                    ->setNext(new CustomerArea($customer, $data['area_id']))
                    ->setNext(new CustomerTags($customer))
                    ->setNext($promoRedeemsRule);

            // Start Chaining
            $checkPromotion = new CheckPromotion($promotion);
            $checkPromotion->setRule($rule);
            $checkPromotion->checkPromotionIsValid();
        }


        return true;
    }

    /** Calculate Payment Summary
     *
     * @param array $data
     * @return float
     */
    private function totalItemPrice(array $items, $promotion = null) {

        $productsFromDB = Product::whereIn('id', array_column($items, 'id'))->get();
        $excludedProductsFromDB = null;
        if (!is_null($promotion->excluded_from_categories_offer) && $promotion->excluded_from_categories_offer) {
            $categories = config('robosto.EXCLUDED_CATEGORIES');
            $excludedProductsFromDB = Product::whereHas('subCategories', function ($q) use ($categories) {
                        $q->whereHas('parentCategories', function ($q2) use ($categories) {
                            $q2->whereIn('categories.id', $categories);
                        });
                    })->whereIn('id', array_column($items, 'id'))->get();
        }
        $total = 0;

        foreach ($items as $item) {
            if (!is_null($excludedProductsFromDB) && $excludedProductsFromDB->where('id', $item['id'])->isNotEmpty()) {
                continue;
            }
            $product = $productsFromDB->where('id', $item['id'])->first();
            Log::info(["item :" => ['id' => $item['id'], 'qty' => $item['qty'], 'price' => $product['price'], 'tax' => $product['tax']]]);
            $total += $product->tax + ($product->price * $item['qty']);
        }
        Log::info('total price for promotion:  ' .$total);
        return $total;
    }

    private function extraPromotionRules($promotion) {
        $extraPromotionRules = collect(config('robosto.EXTERA_PROMOTOION_RULES'));
        $extraPromotionCollection = $extraPromotionRules->where('promo_code_id', $promotion->id)->first();
        if ($extraPromotionCollection) {
            $promotion->max_item_qty = isset($extraPromotionCollection['max_item_qty']) ? $extraPromotionCollection['max_item_qty'] : null;
            $promotion->excluded_from_categories_offer = isset($extraPromotionCollection['excluded_from_categories_offer']) ? $extraPromotionCollection['excluded_from_categories_offer'] : null;
        }

        return $promotion;
    }

    /** Calculate Payment Summary
     *
     * @param array $data
     * @return float
     */
    private function sumItemsQty(array $items) {
        $total = 0;

        foreach ($items as $item) {
            $total += $item['qty'];
        }

        return $total;
    }

    /**
     * Update Inventory Warehouse
     *
     * @param array $items
     * @return void
     */
    private function decreaseInventoryArea(array $items, int $area_id) {
        // Loop through Items and Decrease each quantity from Area
        foreach ($items as $item) {
            $productInInventoryArea = InventoryArea::where('product_id', $item['id'])
                    ->where('area_id', $area_id)
                    ->first();

            $productInInventoryArea->total_qty = $productInInventoryArea->total_qty - $item['qty'];
            $productInInventoryArea->save();
        }
    }

    /**
     * @param OrderModel $order
     * @return JsonResponse
     */
    public function show(OrderModel $order) {
        $data = new OrderResource($order);
        return $this->responseSuccess($data);
    }

    /**
     * @param OrderModel $order
     * @return JsonResponse
     */
    public function updateDriverAndSchedul(OrderModel $order, Request $request) {

        $this->validate($request, [
            'scheduled_at' => 'nullable',
            'assigned_driver_id' => 'nullable|numeric|exists:drivers,id'
        ]);
        $data = $request->only(['scheduled_at', 'assigned_driver_id']);

        // Update Schedule at
        $this->updateOrderScheduled($order, $data);

        // Update Assigned Driver
        $this->updateOrderAssignedDriver($order, $data);

        return $this->responseSuccess(new OrderResource($order));
    }

    /**
     * @param OrderModel $order
     * @param array $data
     *
     * @return void
     */
    private function updateOrderScheduled(OrderModel $order, array $data) {
        if (isset($data['scheduled_at']) && !empty($data['scheduled_at'])) {

            if ($order->status != OrderModel::STATUS_SCHEDULED) {
                throw new ResponseErrorException(410, 'Cannot Update the order at this status');
            }

            $hours = config('robosto.ORDER_SCHEDULE_TIME_BUFFER');
            // if schedule time gived
            $givinTime = Carbon::createFromTimestamp($data['scheduled_at']);

            if ($givinTime < now()->addHours($hours)) {
                throw new ResponseErrorException(410, __('sales::app.shcedulTimeNotValid'));
            }

            $order->scheduled_at = Carbon::createFromTimestamp($data['scheduled_at'])->format('Y-m-d H:i:s');
            $order->save();
        }
    }

    /**
     * @param OrderModel $order
     * @param array $data
     *
     * @return void
     */
    private function updateOrderAssignedDriver(OrderModel $order, array $data) {
        if (isset($data['assigned_driver_id']) && !empty($data['assigned_driver_id'])) {
            $oldDriverID = $order->assigned_driver_id;
            // Get the driver
            $driver = Driver::findOrFail($data['assigned_driver_id']);

            // Validate Assigned Driver
            $this->validateAssignedDriver($order, $driver);

            // if the order has driver before, then update driver with new driver
            if ($order->driver_id) {
                $order->driver_id = $data['assigned_driver_id'];
            }

            // Assign the driver to the order
            $order->assigned_driver_id = $data['assigned_driver_id'];
            $order->save();

            // Update Driver
            // if ($driver->multi_order == '0') {
            //     $driver->can_receive_orders = Driver::CANNOT_RECEIVE_ORDERS;
            // }
            // Redispatch Order via RoboDistance Service if the assigned driver is default driver.
            if ($driver->default_driver == Driver::DEFAULT_DRIVER) {
                RoboDistanceJob::dispatch($order);
            }else{
                Event::dispatch('driver.new-order-assigned', [$order->id]);
            }
            $driver->availability = Driver::AVAILABILITY_DELIVERY;
            $driver->save();
            if($oldDriverID){
                // get old driver
                $oldDriver = Driver::findOrFail($oldDriverID);
                if ($oldDriver->on_the_way == false) {
                    $oldDriver->availability = Driver::AVAILABILITY_BACK;
                    $oldDriver->can_receive_orders = Driver::CAN_RECEIVE_ORDERS;
                    $oldDriver->save();
                }
            }

            Event::dispatch('driver.order-cancelled', $order->id);
        }
    }

    /**
     * @param OrderModel $order
     * @param Driver $driver
     *
     * @return void
     */
    private function validateAssignedDriver(OrderModel $order, Driver $driver) {
        $notValidStatus = [OrderModel::STATUS_SCHEDULED, OrderModel::STATUS_AT_PLACE, OrderModel::STATUS_DELIVERED, OrderModel::STATUS_CANCELLED, OrderModel::STATUS_CANCELLED_FOR_ITEMS, OrderModel::STATUS_EMERGENCY_FAILURE];
        if (in_array($order->status, $notValidStatus)) {
            throw new ResponseErrorException(410, __('sales::app.orderStatusCannotUpdateNow'));
        }

        // Check that the driver can receive orders
        // if ($driver->can_receive_orders == Driver::CANNOT_RECEIVE_ORDERS) {
        //     throw new ResponseErrorException(410, __('sales::app.driverCannotReceiveOrdersNow'));
        // }
        // check if the order has driver before, and the given driver isn't belongs to the order warehouse.
        if ($order->driver_id && $driver->warehouse_id != $order->warehouse_id) {
            throw new ResponseErrorException(410, __('The Given driver does not belongs to the warehouse'));
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function onlineCollectors(Request $request) {
        $this->validate($request, [
            'order_id' => 'required|numeric'
        ]);

        $order = $this->orderRepository->findOrFail($request->order_id);

        if ($order->status != OrderModel::STATUS_PREPARING) {
            throw new ResponseErrorException(410, __('sales::app.orderStatusCannotUpdateNow'));
        }

        $collectors = Collector::where('warehouse_id', $order->warehouse_id)
                ->where('can_receive_orders', Collector::CAN_RECEIVE_ORDERS)
                ->where('status', 1)
                ->where('availability', Collector::AVAILABILITY_IDLE);

        $collectors = $collectors->get();

        $data = new OnlineCollectors($collectors);

        return $this->responsePaginatedSuccess($data, null, $request);
    }

    /**
     * @param OrderModel $order
     * @param array $data
     *
     * @return void
     */
    public function assignOrderToCollector(OrderModel $order, Request $request) {
        $this->validate($request, [
            'collector_id' => 'required|numeric'
        ]);

        $data = $request->only('collector_id');

        if ($order->status != OrderModel::STATUS_PREPARING) {
            throw new ResponseErrorException(410, __('sales::app.orderStatusCannotUpdateNow'));
        }

        if (isset($data['collector_id']) && !empty($data['collector_id'])) {

            // Get the driver
            $collector = Collector::findOrFail($data['collector_id']);

            // Validate Assigned Driver
            $this->validateAssignedCollector($order, $collector);

            // Assign the driver to the order
            $order->collector_id = $data['collector_id'];
            $order->save();

            return $this->responseSuccess();
        }
    }

    /**
     * @param OrderModel $order
     * @param Collector $driver
     *
     * @return void
     */
    private function validateAssignedCollector(OrderModel $order, Collector $collector) {
        // Check that the driver can receive orders
        if ($collector->can_receive_orders == Collector::CANNOT_RECEIVE_ORDERS || $collector->availability == Collector::AVAILABILITY_OFFLINE || $collector->status != 1) {
            throw new ResponseErrorException(410, __('sales::app.collectorCannotReceiveOrder'));
        }

        // check if the order has collector before, and the given collector isn't belongs to the order warehouse.
        if ($order->collector_id && $collector->warehouse_id != $order->warehouse_id) {
            throw new ResponseErrorException(410, __('sales::app.collectorDoesntBelongsToWarehouse'));
        }
    }

    /**
     * @param OrderModel $order
     * @param UpdateOrderRequest $request
     *
     * @return mixed
     */
    public function update(OrderModel $order, UpdateOrderRequest $request) {
        if($order->paid_type== OrderModel::PAID_TYPE_BNPL && $order->is_paid== OrderModel::ORDER_PAID){
            throw new PlaceOrderValidationException(410, 'Order can not be updated');
        }
        $data = $request->only(['promo_code', 'items']);
        $data['call_enter'] = auth($this->guard)->id();
        $data['customer_id'] = $order->customer_id;
        // Apply White Friday Offer
        $data = $this->applyWhiteFridayOffer($data);
        Log::info('HERE UPDATE 1');

        $this->checkFreeShippingCoupon($data);

        Log::info('HERE UPDATE 2');

        // First, check the order can be updated
        $this->checkOrderCanAcceptUpdating($order);
        Log::info('HERE UPDATE 3');

        $status = $this->mergeOrderStatus($order);
        if($order->paid_type== OrderModel::PAID_TYPE_BNPL && $order->is_paid== OrderModel::ORDER_PAID){
            $data['items'] = $this->addMarginToItems($data['items']);
        }
        Log::info('HERE UPDATE 4');

        // deattach bundle items and merge them to merged itmes
        $data['merged_items'] = $this->getMergedItems($data['items']);
        Log::info('HERE UPDATE 5');

        // Define Update Order Validation Rules
        $this->checkUpdateOrderValidation($order, $data, $status);
        Log::info('HERE UPDATE 6');

        DB::beginTransaction();
        try {
            // Update Order Items
            $this->updateOrderItems($order, $data, $status);
            // Flag the Order As Updated
            $order->is_updated = '1';
            $order->save();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
        DB::commit();
        Log::info('HERE UPDATE 7');

        // Update Bundle Products stock In Inventory Area And Inventory Warehouse
        $this->updateBundleProductsStockInAreaAndWarehouse($order);
        Log::info('HERE UPDATE 8');

        // Cache Updated Order, to get then in collector
        $this->cacheUpdatedOrder($order, $status);

        return $this->responseSuccess(null, 'Order updated successfully');
    }
    public function addMarginToItems(array $items) {
        for($i=0;$i<count($items); $i++){
            $items[$i]['margin']= config('robosto.BNPL_INTEREST');
        }
        return $items;
    }
    /**
     * @param OrderModel $order
     *
     * @return mixed
     */
    private function checkOrderCanAcceptUpdating(OrderModel $order) {
        $availableStatus = [
            OrderModel::STATUS_PENDING, OrderModel::STATUS_WAITING_CUSTOMER_RESPONSE, OrderModel::STATUS_PREPARING,
            OrderModel::STATUS_READY_TO_PICKUP, OrderModel::STATUS_ON_THE_WAY, OrderModel::STATUS_AT_PLACE
        ];

        if (!in_array($order->status, $availableStatus)) {
            throw new ResponseErrorException(410, __('sales::app.orderCannotUpdate'));
        }
    }

    /**
     * @param OrderModel $order
     *
     * @return string
     */
    private function mergeOrderStatus(OrderModel $order) {
        $pedningStatus = [OrderModel::STATUS_PENDING, OrderModel::STATUS_WAITING_CUSTOMER_RESPONSE];

        $activeStatus = [OrderModel::STATUS_PREPARING, OrderModel::STATUS_READY_TO_PICKUP, OrderModel::STATUS_ON_THE_WAY, OrderModel::STATUS_AT_PLACE];

        if (in_array($order->status, $pedningStatus)) {
            return 'pending';
        }

        if (in_array($order->status, $activeStatus)) {
            return 'active';
        }
    }

    /**
     * @param OrderModel $order
     * @param array $data
     * @param string $status
     *
     * @return void
     */
    private function checkUpdateOrderValidation(OrderModel $order, array $data, string $status) {
        $data = $this->prepareExistOrderItemsForUpdate($order, $data);

        $rule = new CheckCallCenterTimeOutRule($data);

        if ($status == 'pending') {
            $rule->setNext(new CheckItemsInAreaRule($order->customer, $order->area_id));
        } else {
            $rule->setNext(new CheckItemsInWarehouseRule($order->area_id, count($data['merged_items']) == 0));
        }

        // 1- Start Order Validation Chaining
        $checkPlaceOrder = new CheckPlaceOrderIsAllowed($data['merged_items']);
        $checkPlaceOrder->setRule($rule);
        $checkPlaceOrder->checkPlaceOrderIsAllowed();
    }

    /**
     * Prepare Difference in Quantity between given items and order items
     *
     * @param OrderModel $order
     * @param array $data
     *
     * @return array
     */
    private function prepareExistOrderItemsForUpdate(OrderModel $order, array $data) {
        $mergedOrderItems = $this->mergedItemsFromCollection($order->items()->get());

        foreach ($mergedOrderItems as $item) {
            foreach ($data['merged_items'] as $key => $givenItem) {

                if ($item['id'] == $givenItem['id']) {
                    if ($givenItem['qty'] > $item['qty']) {
                        $data['merged_items'][$key]['qty'] = $givenItem['qty'] - $item['qty'];
                    } else {
                        if (!$this->checkOrderWaitingResponse($order, $givenItem)) {
                            unset($data['merged_items'][$key]);
                        }
                    }
                }
            }
        }
        return $data;
    }

    /**
     * @param OrderModel $order
     * @param array $data
     * @param string $status
     *
     * @return void
     */
    private function updateOrderItems(OrderModel $order, array $data, string $status) {
        $this->saveOldOrederItems($order);

        $this->handleNewOrederItems($order, $data);

        $this->updateInventory($order, $status);
    }

    /**
     * @param OrderModel $order
     *
     * @return void
     */
    private function saveOldOrederItems(OrderModel $order) {

        $order->oldItems()->delete(); // clear data from OldOrderItems for this order
        $order->oldSkus()->delete(); // clear data from OldOrderItemskus for this order
        // increase inventory products
        $this->salesRepository->increaseInventoryProduct($order);

        // Save Old Items
        foreach ($order->items()->get() as $item) {
            $orderItem = $order->oldItems()->create($item->toArray());
            // start delete old sku
            //$skus = $item->skus()->get();
            foreach ($item->skus()->get() as $sku) {
                $data = $sku->toArray();
                unset($data['order_item_id']); // its already removed from db will make confilect in relation
                $orderItem->skus()->create($data);
            }
            ///////////////////////////////////////////////////////
        }
        // Delete Old Sku
        $order->skus()->delete();
        // Delete Old Items
        $order->items()->delete();
    }

    /**
     * @param OrderModel $order
     *
     * @return void
     */
    private function handleNewOrederItems(OrderModel $order, array $data) {
        $promotion = null;

        // Save Order Items
        if (isset($data['promo_code']) && !empty($data['promo_code'])) {
            // Get Promotion
            $promotion = Promotion::where('promo_code', $data['promo_code'])->first();
            $updateOrder = false;
            if ($order->promotion_id == $promotion->id) {
                $updateOrder = true;
            }
            $this->salesRepository->saveOrderItemsWithPromotion($order, $data['items'], $promotion, $updateOrder);
        } else {
            $this->salesRepository->saveOrderItems($order, $data['items']);
        }

        // Return Old Customer Balance to the customer, and then re-calculate order again
        if ($order->customer_balance != 0) {
            if ($order->customer_balance > 0) {
                // Minus amount from customer wallet
                $this->salesRepository->subtractMoneyFromCustomerWallet($order, abs($order->customer_balance));
            } else {
                // Plus amount to customer wallet
                $this->salesRepository->addMoneyToCustomerWallet($order, abs($order->customer_balance));
            }
        }
        // Update Master Order Price and Quantities
        $this->salesRepository->updateOrderWithCalculations($order, $data, $promotion, true);

        if($order->paid_type==OrderModel::PAID_TYPE_BNPL){
            $this->salesRepository->executeBNPLRequest($order,true);
        }
    }

    /**
     * @param OrderModel $order
     * @param array $data
     * @param string $status
     *
     * @return void
     */
    private function updateInventory(OrderModel $order, string $status) {

        // Megre Bundle Items with Original Items
        $newItems = collect($this->mergedItemsFromCollection($order->items()->get()));
        $oldItems = collect($this->mergedItemsFromCollection($order->oldItems()->get()));

        if ($status == 'pending') {
            $this->updateOrderItemsInArea($order, $oldItems, $newItems);
        } else {
            $this->updateOrderItemsInArea($order, $oldItems, $newItems);
            $this->updateOrderItemsInWarehouse($order, $oldItems, $newItems);
            // call decreaseInventoryProduct($order)
            $this->salesRepository->decreaseInventoryProduct($order);
        }

        // Adjustment Extra Items
        $this->adjustmentExtraItems($order, $oldItems, $newItems, $status);
    }

    /**
     * @param OrderModel $order
     * @param SupportCollection $oldItems
     * @param SupportCollection $newItems
     *
     * @return void
     */
    private function updateOrderItemsInArea(OrderModel $order, SupportCollection $oldItems, SupportCollection $newItems) {
        foreach ($oldItems as $oldItem) {
            $newItem = $newItems->where('id', $oldItem['id'])->first();
            $InventoryArea = InventoryArea::where('product_id', $oldItem['id'])->where('area_id', $order->area_id)->first();
            if ($newItem) {
                Log::info(">>>>>>>>>>>>>>>>>>>>>>>>>>>>>");
                Log::alert("Product => " . $oldItem['id']);
                Log::alert("New QTY => " . $newItem['qty'] . " - Old QTY " . $oldItem['qty']);
                Log::alert("RECENT => " . $InventoryArea->total_qty);
                // 12  > 5
                if ($newItem['qty'] > $oldItem['qty']) {
                    Log::info("Decrease => " . ($newItem['qty'] - $oldItem['qty']));
                    // Decrease 7 from area
                    $InventoryArea->total_qty -= ($newItem['qty'] - $oldItem['qty']); // ( 12 - 5 ) = 7
                } elseif ($newItem['qty'] < $oldItem['qty']) {
                    Log::info("Increase =>  " . ($oldItem['qty'] - $newItem['qty']));
                    // Increase 5 to Area
                    $InventoryArea->total_qty += ($oldItem['qty'] - $newItem['qty']);   // ( 6 - 1 ) = 5
                }
            } else {
                // Return Old Quantity to Area
                if ($InventoryArea) {
                    $InventoryArea->total_qty += $oldItem['qty'];
                }
            }
            Log::alert("Before Save => " . $InventoryArea->total_qty);
            Log::info("<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<");
            $InventoryArea->save();
        }
    }

    /**
     * @param OrderModel $order
     * @param SupportCollection $oldItems
     * @param SupportCollection $newItems
     *
     * @return void
     */
    private function updateOrderItemsInWarehouse(OrderModel $order, SupportCollection $oldItems, SupportCollection $newItems) {
        foreach ($oldItems as $oldItem) {
            $newItem = $newItems->where('id', $oldItem['id'])->first();
            $InventoryWarehouse = InventoryWarehouse::where('product_id', $oldItem['id'])
                            ->where('area_id', $order->area_id)->where('warehouse_id', $order->warehouse_id)->first();
            if ($newItem) {
                // get Difference in Quantity
                // 12  > 5
                if ($newItem['qty'] > $oldItem['qty']) {
                    // Decrease 7 from area
                    $InventoryWarehouse->qty -= ($newItem['qty'] - $oldItem['qty']); // ( 12 - 5 ) = 7
                } elseif ($newItem['qty'] < $oldItem['qty']) {
                    // Increase 5 to Area
                    $InventoryWarehouse->qty += ($oldItem['qty'] - $newItem['qty']);   // ( 6 - 1 ) = 5
                }
            } else {
                // Return Old Quantity to Area
                if ($InventoryWarehouse) {
                    $InventoryWarehouse->qty += $oldItem['qty'];
                }
            }
            $InventoryWarehouse->save();
        }
    }

    /**
     * @param OrderModel $order
     * @param SupportCollection $oldItems
     * @param SupportCollection $newItems
     * @param string $status
     *
     * @return void
     */
    private function adjustmentExtraItems(OrderModel $order, SupportCollection $oldItems, SupportCollection $newItems, string $status) {
        Log::info("Start Save Extra Items");
        foreach ($newItems as $item) {

            $checkExist = $oldItems->where('id', $item['id'])->isEmpty();
            if ($checkExist) {
                Log::alert("Product {$item['id']} Exist In Extra Items");
                // Decrease from Area
                $InventoryArea = InventoryArea::where('product_id', $item['id'])->where('area_id', $order->area_id)->first();
                $InventoryArea->total_qty -= $item['qty'];
                $InventoryArea->save();

                // Decrease from Warehouse if order status is active
                if ($status == 'active') {
                    $InventoryWarehouse = InventoryWarehouse::where('product_id', $item['id'])
                                    ->where('area_id', $order->area_id)->where('warehouse_id', $order->warehouse_id)->first();
                    $InventoryWarehouse->qty -= $item['qty'];
                    $InventoryWarehouse->save();
                }
            }
        }
        Log::info("Finish Save Extra Items");
    }

    /**
     * @param OrderModel $order
     * @param array $newItems
     *
     * @return void
     */
    private function oldUpdateOrderItemsInArea(OrderModel $order, Collection $newItems) {
        foreach ($order->oldItems()->get() as $oldItem) {
            $newItem = $newItems->where('product_id', $oldItem->product_id)->first();
            $InventoryArea = InventoryArea::where('product_id', $oldItem->product_id)->where('area_id', $order->area_id)->first();
            if ($newItem) {
                // get Difference in Quantity
                // 12  > 5
                if ($newItem->qty_ordered > $oldItem->qty_shipped) {
                    // Decrease 7 from area
                    $InventoryArea->total_qty -= ($newItem->qty_ordered - $oldItem->qty_shipped); // ( 12 - 5 ) = 7
                } elseif ($newItem->qty_ordered < $oldItem->qty_shipped) {
                    // Increase 5 to Area
                    $InventoryArea->total_qty += ($oldItem->qty_shipped - $newItem->qty_ordered);   // ( 6 - 1 ) = 5
                }
            } else {
                // Return Old Quantity to Area
                if ($InventoryArea) {
                    $InventoryArea->total_qty += $oldItem->qty_shipped;
                }
            }
            $InventoryArea->save();
        }
    }

    /**
     * @param OrderModel $order
     * @param array $newItems
     *
     * @return void
     */
    private function oldUpdateOrderItemsInWarehouse(OrderModel $order, Collection $newItems) {
        foreach ($order->oldItems as $oldItem) {
            $newItem = $newItems->where('product_id', $oldItem->product_id)->first();
            $InventoryWarehouse = InventoryWarehouse::where('product_id', $oldItem->product_id)
                            ->where('area_id', $order->area_id)->where('warehouse_id', $order->warehouse_id)->first();
            if ($newItem) {
                // get Difference in Quantity
                // 12  > 5
                if ($newItem->qty_ordered > $oldItem->qty_shipped) {
                    // Decrease 7 from area
                    $InventoryWarehouse->qty -= ($newItem->qty_ordered - $oldItem->qty_shipped); // ( 12 - 5 ) = 7
                } elseif ($newItem->qty_ordered < $oldItem->qty_shipped) {
                    // Increase 5 to Area
                    $InventoryWarehouse->qty += ($oldItem->qty_shipped - $newItem->qty_ordered);   // ( 6 - 1 ) = 5
                }
            } else {
                // Return Old Quantity to Area
                if ($InventoryWarehouse) {
                    $InventoryWarehouse->qty += $oldItem->qty_shipped;
                }
            }
            $InventoryWarehouse->save();
        }
    }

    /**
     * @param OrderModel $order
     * @param collection $newItems
     * @param string $status
     *
     * @return void
     */
    private function oldAdjustmentExtraItems(OrderModel $order, collection $newItems, string $status) {
        $extraItems = $newItems->whereNotIn('product_id', $order->oldItems->pluck('product_id')->toArray());
        foreach ($extraItems as $item) {

            // Decrease from Area
            $InventoryArea = InventoryArea::where('product_id', $item->product_id)->where('area_id', $order->area_id)->first();
            $InventoryArea->total_qty -= $item->qty_ordered;
            $InventoryArea->save();

            // Decrease from Warehouse if order status is active
            if ($status == 'active') {
                $InventoryWarehouse = InventoryWarehouse::where('product_id', $item->product_id)
                                ->where('area_id', $order->area_id)->where('warehouse_id', $order->warehouse_id)->first();
                $InventoryWarehouse->qty -= $item->qty_ordered;
                $InventoryWarehouse->save();
            }
        }
    }

    /**
     * @param OrderModel $order
     *
     * @return void
     */
    private function cacheUpdatedOrder(OrderModel $order, string $status) {
        if ($status == 'active') {
            $key = "warehouse_{$order->warehouse_id}_updated_orders";
            $updatedOrders = Cache::get($key);
            if ($updatedOrders && count($updatedOrders)) {

                if (!in_array($order->id, $updatedOrders)) {
                    // Store array of order IDs
                    $updatedOrders[] = $order->id;
                    Cache::put($key, $updatedOrders);
                }
            } else {
                // Store array of order IDs
                $updatedOrders[] = $order->id;
                Cache::put($key, $updatedOrders);
            }
        }
    }

    /**
     * Show the specified order.
     *
     * @param $id
     * @return JsonResponse
     */
    public function reOrderDetails($id) {
        $order = $this->orderRepository->findOrFail($id);

        Event::dispatch('app-orders.show', $order);

        $data = new ReOrderDetailsSingle($order);

        return $this->responseSuccess($data);
    }

    /**
     * @param OrderModel $order
     * @return JsonResponse
     */
    public function redispatch(OrderModel $order) {
        $validStatus = [OrderModel::STATUS_PENDING, OrderModel::STATUS_WAITING_CUSTOMER_RESPONSE];
        if (!in_array($order->status, $validStatus)) {
            return $this->responseError(409);
        }

        $this->salesRepository->redispatchOrder($order);

        return $this->responseSuccess();
    }

    /**
     * @param OrderModel $order
     * @return JsonResponse
     */
    public function complaint(OrderComplaintRequest $request) {
        $data = $request->only(['text', 'order_id']);

        $order = $this->salesRepository->findOrFail($data['order_id']);

        $order->complaints()->create([
            'text' => $data['text'],
            'customer_id' => $order->customer_id
        ]);

        return $this->responseSuccess();
    }

    /**
     * @param OrderModel $order
     * @return JsonResponse
     */
    public function noteCreate(OrderNoteRequest $request) {
        $data = $request->only(['text', 'order_id']);
        $order = $this->orderRepository->findOrFail($data['order_id']);

        $order->notes()->create([
            'text' => $data['text'],
            'admin_id' => auth($this->guard)->user()->id,
        ]);

        return $this->responseSuccess();
    }

    public function noteList(Request $request) {
        $data = $request->only(['order_id']);
        $order = $this->orderRepository->findOrFail($data['order_id']);
        $notes = new OrderNotesAll($order->notes);
        return $this->responseSuccess($notes);
    }

    /**
     * @param Order $order
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function violationsList(Order $order, Request $request) {

        $data = new OrderViolations($order->violations);

        return $this->responseSuccess($data);
    }

    public function violationsListAll( Request $request) {
        $violations = $this->orderRepository->allViolations($request);
        $data = new OrderViolations($violations);
        return $this->responsePaginatedSuccess($data, null, $request);
    }

    /**
     * @param Order $order
     *
     * @param OrderModel $order
     * @return JsonResponse
     */
    public function createViolation(Order $order, OrderViolationRequest $request) {
        $data = [
            'type' => $request->type,
            'violation_type' => $request->violation_type,
            'violation_note' => $request->violation_note,
            'admin_id' => auth('admin')->user()->id,
        ];

        $data = $this->checkOrderViolationType($order, $data);

        $order->violations()->create($data);

        return $this->responseSuccess();
    }

    /**
     * @param Order $order
     * @param array $data
     *
     * @return array
     */
    public function checkOrderViolationType(Order $order, array $data) {
        if ($data['type'] == OrderViolation::DRIVER_TYPE) {
            if ($order->driver_id == null) {
                throw new ResponseErrorException(410, "Sorry, the order doesn't have a driver yet, why violate him?!!");
            }
            $data['driver_id'] = $order->driver_id;
            return $data;
        } else if ($data['type'] == OrderViolation::COLLECTOR_TYPE) {
            if ($order->collector_id == null) {
                throw new ResponseErrorException(410, "Sorry, the order doesn't have a collector yet, why violate him?!!");
            }
            $data['collector_id'] = $order->collector_id;
            return $data;
        } else {
            return $data;
        }
    }

    /**
     * @param $data
     * @param null $message
     * @param $request
     * @return JsonResponse
     */
    protected function customResponsePaginatedSuccess($data, $message = null, $request) {
        $response = null;
        if ($data['orders']->resource instanceof LengthAwarePaginator) {
            $response = $data['orders']->toResponse($request)->getData();
        }

        $response->orders_status_count = $data['orders_status_count'];
        $response->status = 200;
        $response->success = true;
        $response->message = $message;
        return response()->json($response);
    }

    /**
     * @param Customer $customer
     * @param Request $request
     * @return JsonResponse
     */
    public function customerOrdersHistoryList(Customer $customer, Request $request) {
        $orders = $this->orderRepository->customerOrdersHistoryList($customer, $request);
        $data = new OrderAll($orders);

        return $this->responsePaginatedSuccess($data, null, $request);
    }

    public function callcenterReturnCustomerOrder(Request $request) {
        $data = $request->only('order_id', 'customer_id', 'return_reason', 'items');

        $order = $this->orderRepository->callcenterReturnCustomerOrder($data);

        Event::dispatch('admin.log.activity', ['order-returned', 'order', $order, auth($this->guard)->user(), $order]);

        return $this->responseSuccess($order);
    }

    /**
     * Cancel Order if Pending | Prepairing
     */
    public function cancelOrder(Request $request) {
        $data = $request->only('order_id', 'reason','cancel_all');

        if (!isset($data['reason']) || !trim($data['reason'])) {
            return $this->responseError(422, 'Please Add Cancellation Order Reason', null);
        }

        // Get the Order
        $order = $this->orderRepository->findOrFail($data['order_id']);

        // first of all, check that the order Can Cancelled
        $availableStatus = [
            Order::STATUS_PENDING, Order::STATUS_WAITING_CUSTOMER_RESPONSE, Order::STATUS_PREPARING,
            Order::STATUS_READY_TO_PICKUP, Order::STATUS_ON_THE_WAY, Order::STATUS_AT_PLACE, Order::STATUS_SCHEDULED
        ];

        if (!in_array($order->status, $availableStatus)) {
            return $this->responseError(422, 'Cannot Cancel the Order', null);
        }

        // Update Order Status to Cancelled
        $this->salesRepository->updateOrderStatus($order, OrderModel::STATUS_CANCELLED);

        // Order Cancel Reason
        if (isset($data['reason']) && $data['reason']) {
            $order->cancelReason()->create(['reason' => $data['reason']]);
        }

        if($order->shippment_id){
            $config = [];
            if(isset($data["cancel_all"])){
                $config["cancel_all"] = $data["cancel_all"];
            }
            ShippmentOrderRouter::dispatch($order,$config);
        }else{
         // Dispatch Cancel Order Job
         CustomerCancelledOrder::dispatch($order);
        }
        Event::dispatch('order.actual_logs', [$order, OrderLogsActual::ORDER_CANCELLED]);
        Event::dispatch('admin.log.activity', ['order-cancelled', 'order', $order, auth($this->guard)->user(), $order]);
        // send notification to operation manager
        $payload['model'] = $order;
        Event::dispatch('admin.alert.admin_cancelled_order', [auth($this->guard)->user(), $payload]);

        Event::dispatch('driver.order-cancelled', $order->id);

        return $this->responseSuccess();
    }


     /**
     * dispatch scheduled Order now
     */
    public function dispatchScheduledOrder(Request $request) {
        $data = $request->only('order_id', 'reason');
        // Get the Order
        $order = $this->orderRepository->findOrFail($data['order_id']);

        if ($order->status != Order::STATUS_SCHEDULED) {
            return $this->responseError(422, 'this is not a scheduled order', null);
        }
        $order->in_queue= 1;
        $order->save();
        TrackingScheduledOrders::dispatch($order);
        return $this->responseSuccess();
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function productsSearch(Request $request) {
        $products = $this->orderRepository->searchForProducts($request);

        $data = new ProductsSearch($products);

        return $this->responsePaginatedSuccess($data, null, $request);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function onlineDrivers(Request $request) {
        $this->validate($request, [
            'area_id' => 'required|numeric',
            'order_id' => 'required|numeric'
        ]);

        $area = $request->area_id;
        $order = $this->orderRepository->findOrFail($request->order_id);

        $drivers = Driver::where('area_id', $area)
                ->where('can_receive_orders', Driver::CAN_RECEIVE_ORDERS)
                ->where('status', 1)
                ->whereNotIn('availability', [Driver::AVAILABILITY_EMERGENCY]);

        if ($order->driver_id) {
            $drivers = $drivers->where('warehouse_id', $order->warehouse_id);
        }

        $drivers = $drivers->get();

        $data = new OnlineDrivers($drivers);

        return $this->responsePaginatedSuccess($data, null, $request);
    }

    /**
     * @param array $items
     * @return array
     */
    private function prepareItemsForResponse(array $data, array $items, $area = false) {

        $dataItems = $area ? $data['merged_items'] : $data['items'];
        // Load Products from DB once
        $allItems = collect($dataItems)->pluck('id')->toArray();
        $allItemsFromDB = Product::whereIn('id', $allItems)->get();

        // Prepare Items for each type
        $notEnough = $this->prepareNotEnoughItems($dataItems, $items, $allItemsFromDB);
        $outOfStock = $this->prepareOutOfStockItems($dataItems, $items, $allItemsFromDB);
        $availableItems = $this->prepareAvailableItems($dataItems, $notEnough, $outOfStock, $allItemsFromDB);

        // Handle Payment Summary
        $cartItems = $this->handleItemsForCart($notEnough, $outOfStock, $availableItems);

        // Data Required to calculate payment summary
        $data = [
            'items' => $cartItems,
            'customer' => Customer::find($data['customer_id']),
            'promo_code' => $data['promo_code'] ?? null,
        ];
        Log::info('log data before payment summary');
        Log::info($data);
        // Calculate Payment Summary
        $paymentSummary = $this->paymentSummary($data);

        return [
            'not_enough' => $notEnough,
            'out_of_stock' => $outOfStock,
            'payment_summary' => $paymentSummary,
        ];
    }

    /**
     * @param array $originalItems
     * @param array $items
     * @param Collection $allItemsFromDB
     *
     * @return array
     */
    private function prepareNotEnoughItems(array $originalItems, array $items, Collection $allItemsFromDB) {
        if (empty($items['not_enough'])) {
            return [];
        }

        // Prepare Not enough Product Data
        foreach ($items['not_enough'] as $item) {
            $product = $allItemsFromDB->where('id', $item['product_id'])->first();

            $notEnoughItems[] = [
                'id' => $product->id,
                'image_url' => $product->image_url,
                'thumb_url' => $product->thumb_url,
                'price' => $product->price,
                'unit_name' => $product->unit->name,
                'unit_value' => $product->unit_value,
                'name' => $product->name,
                'qty_ordered' => collect($originalItems)->where('id', $item['product_id'])->first()['qty'],
                'available_qty' => $item['available_qty'],
            ];
        }

        return $notEnoughItems;
    }

    /**
     * @param array $originalItems
     * @param array $items
     * @param Collection $allItemsFromDB
     *
     * @return array
     */
    private function prepareOutOfStockItems(array $originalItems, array $items, Collection $allItemsFromDB) {
        if (empty($items['out_of_stock'])) {
            return [];
        }

        // Prepare out of stock Product Data
        foreach ($items['out_of_stock'] as $item) {

            if (isset($item['product_id'])) {
                $productID = $item['product_id'];
            } else {
                $productID = $item['id'];
            }
            $product = $allItemsFromDB->where('id', $productID)->first();

            $outOfStockItems[] = [
                'id' => $product->id,
                'image_url' => $product->image_url,
                'thumb_url' => $product->thumb_url,
                'price' => $product->price,
                'unit_name' => $product->unit->name,
                'unit_value' => $product->unit_value,
                'name' => $product->name,
                'qty_ordered' => collect($originalItems)->where('id', $productID)->first()['qty'],
            ];
        }

        return $outOfStockItems;
    }

    /**
     * @param array $originalItems
     * @param array $notEnough
     * @param array $outOfStock
     * @param Collection $allItemsFromDB
     *
     * @return array
     */
    private function prepareAvailableItems(array $originalItems, array $notEnough, array $outOfStock, Collection $allItemsFromDB) {
        $availableItems = [];
        $outProducts = array_merge(array_column($outOfStock, 'id'), array_column($notEnough, 'id'));
        $validOrderItems = $allItemsFromDB->whereNotIn('id', $outProducts);

        // Prepare Available Data
        foreach ($validOrderItems as $product) {
            $availableItems[] = [
                'id' => $product->id,
                'image_url' => $product->image_url,
                'thumb_url' => $product->thumb_url,
                'price' => $product->price,
                'unit_name' => $product->unit->name,
                'unit_value' => $product->unit_value,
                'name' => $product->name,
                'qty_ordered' => collect($originalItems)->where('id', $product->id)->first()['qty'],
            ];
        }
        return $availableItems;
    }

    /**
     * @param array $notEnough
     * @param array $outOfStock
     * @param array $availableItems
     *
     * @return [type]
     */
    private function handleItemsForCart(array $notEnough, array $outOfStock, array $availableItems) {
        $items = [];
        foreach ($notEnough as $item) {
            $items[] = [
                'id' => $item['id'],
                'qty' => (int) $item['available_qty'],
                'price' => (int) $item['price'],
            ];
        }

        foreach ($outOfStock as $item) {
            $items[] = [
                'id' => $item['id'],
                'qty' => 0,
                'price' => (int) $item['price'],
            ];
        }

        foreach ($availableItems as $item) {
            $items[] = [
                'id' => $item['id'],
                'qty' => (int) $item['qty_ordered'],
                'price' => (int) $item['price'],
            ];
        }

        return $items;
    }

    /**
     * @param array $items
     *
     * @return [type]
     */
    private function handleValidItemsForCart(array $items) {
        $newItems = [];
        foreach ($items as $item) {
            $margin = isset($item['margin']) ? $item['margin'] : 0;
            $product = Product::find($item['id']);
            $product->price = $product->price + ($product->price * $margin );

            $newItems[] = [
                'id' => $item['id'],
                'qty' => (int) $item['qty'],
                'price' => $product->price,
            ];
        }

        return $newItems;
    }

    /** Calculate Payment Summary
     * @param array $data
     * @return array
     */
    public function paymentSummary(array $data) {
        $deliver_fees = config('robosto.DELIVERY_CHARGS');
        $total = 0;

        foreach ($data['items'] as $item) {
            $total += $item['price'] * $item['qty'];
        }

        $amountToPay = $total;
        $customerWallet = 0;

        // Apply Wallet if user Exist
        if (isset($data['customer'])) {
            $customerWallet = $data['customer']->wallet;
            $amountToPay -= $customerWallet;
        }

        if ($amountToPay < 0) {
            $amountToPay = 0;
        }

        $summary = [
            'basket_total' => $total,
            'balance' => (float) $customerWallet,
            'amount_to_pay' => $amountToPay
        ];

        // Handle Promotion Code
        $summary = $this->handlePromoCode($data, $summary);

        // Handle Referral Code on the First Order
        $summary = $this->handleCustomerFirstOrder($data, $summary);

        // Handle Delivery fees
        $deliverFees = $this->handleDeliveryFees($data, $summary['basket_total']);
        $deliverFees = $this->handleFreeShippingCoupon($data, $deliverFees);
        $summary['amount_to_pay'] += $deliverFees;
        $summary['delivery_fees'] = $deliverFees;

        // Handle Long Decimal Numbers
        $summary['amount_to_pay'] = round($summary['amount_to_pay'], 2);
        if (isset($summary['discount'])) {
            $summary['discount'] = round($summary['discount'], 2);
        }

        return $summary;
    }

    /**
     * @param array $data
     * @param float $summary
     *
     * @return float
     */
    private function handleDeliveryFees(array $data, float $total) {
        $deliverFees = config('robosto.DELIVERY_CHARGS');

        if ($total >= config('robosto.MINIMUM_ORDER_AMOUNT')) {
            return 0;
        }
        return $deliverFees;
    }

    /**
     * @param array $data
     * @param float $fees
     *
     * @return float
     */
    private function handleFreeShippingCoupon(array $data, float $fees) {
        if (isset($data['free_shipping']) && $data['free_shipping'] == true) {
            $promotion = Promotion::where('promo_code', config('robosto.FREE_SHIPPING_COUPON'))->first();
            if ($promotion) {
                $customer = $data['customer'];

                // Check Customer Tags Exist Promotion Tags
                $customerTags = $customer->tags()->pluck('tags.id')->toArray();
                $promotionTags = $promotion->tags()->pluck('tags.id')->toArray();

                // Check Customer has at least Tag in Promotion Tags
                if (count(array_intersect($customerTags, $promotionTags)) == 0) {
                    Log::info('Free Shipping Coupon Not Valid For Tag');
                    throw new PromotionValidationException(406, __('customer::app.notValidPromoCode'));
                }

                return 0;
            }
        }

        return $fees;
    }

    /**
     * @param array $data
     *
     * @return array
     */
    private function checkFreeShippingCoupon(array &$data) {
        if (isset($data['promo_code']) && ($data['promo_code'] == config('robosto.FREE_SHIPPING_COUPON') || $data['promo_code'] == 'Free')) {
            unset($data['promo_code']);
            $data['free_shipping'] = true;
        }
    }

    /**
     * Handle Promotion Code
     *
     * @param array $data
     * @param array $summary
     *
     * @return array
     */
    public function handlePromoCode(array $data, array $summary) {

        $total = $summary['basket_total'];
        $amountToPay = $summary['amount_to_pay'];
        // Handle Promotion Code
        if (isset($data['promo_code']) && !empty($data['promo_code'])) {
            $summary['coupon'] = $data['promo_code'];
            // Get the Promotion
            $promotion = Promotion::where('promo_code', $data['promo_code'])->first();

            // Apply Promotion On Items
            $applyPromotion = new ApplyPromotion($promotion, $data['items']);
            $products = $applyPromotion->apply();

            // Just Apply Discount on Applicable given Items
            if (!empty($products['discounted_items'])) {

                Log::info('Discounted');
                Log::info($products['discounted_items']);
                $discount = $this->totalPriceDiscounted($products['discounted_items']);
                Log::info($discount);

                $summary['discount'] = $discount;
                if ($amountToPay > $discount) {
                    $summary['amount_to_pay'] -= $discount;
                } else {
                    $summary['amount_to_pay'] = 0;
                }
            }
        }

        return $summary;
    }

    /**
     * Handle Referral Code on the First Order
     *
     * @param array $data
     * @param array $summary
     *
     * @return array
     */
    public function handleCustomerFirstOrder(array $data, array $summary) {
        // Handle Referral Code on the First Order
        if (isset($data['customer'])) {

            $customer = $data['customer'];
            // if the first order
            if ($customer->invitation_applied == 0 && !is_null($customer->invited_by)) {
                // if this customer already toked 25% discount
                $customerActiveOrders = $customer->activeOrders;
                if ($customerActiveOrders->isNotEmpty()) {
                    return $summary;
                }
                Log::info($summary);
                $summary = $this->handleExcludedCategories($data, $summary);
                Log::info($summary);
                // Apply 25% Discount on the Order Total
                $percentage = config('robosto.ORDER_INVITE_CODE_GIFT');
                $discount = ($percentage / 100) * $summary['amount_after_exclude'];

                $summary['discount'] = $discount;
                $summary['coupon'] = $customer->invitedBy->referral_code;
                $summary['amount_to_pay'] -= $discount;

                unset($summary['amount_after_exclude']);
            }
        }

        return $summary;
    }

    /**
     * @param array $data
     * @param array $summary
     *
     * @return array
     */
    public function handleExcludedCategories(array $data, array $summary) {
        $excludedCategories = config('robosto.EXCLUDED_CATEGORIES');
        $excludedProductPrices = 0;
        $summary['amount_after_exclude'] = $summary['amount_to_pay'];
        foreach ($data['items'] as $item) {

            $check = Product::where('id', $item['id'])->whereHas('subCategories', function (Builder $query) use ($excludedCategories) {
                        $query->whereHas('parentCategories', function (Builder $query) use ($excludedCategories) {
                            $query->whereIn('category_id', $excludedCategories);
                        });
                    })->first();

            if ($check) {
                $excludedProductPrices += ($check->price * $item['qty']);
            }
        }

        $summary['amount_after_exclude'] -= $excludedProductPrices;

        return $summary;
    }

    /**
     * @param array $items
     *
     * @return int|float
     */
    private function totalPriceDiscounted(array $items) {
        $totalPriceDiscounted = 0;
        foreach ($items as $item) {
            $totalPriceDiscounted += ($item['total_price'] - $item['total_discounted_price']);
        }
        return $totalPriceDiscounted;
    }

    /**
     * @param array $items
     * @return array
     */
    private function prepareItemsForChecking(array $items) {
        $newItems = [];
        foreach ($items as $item) {
            $newItems[] = [
                'product_id' => $item['id'],
                'qty_ordered' => $item['qty'],
                'qty_shipped' => $item['qty']
            ];
        }

        return $newItems;
    }

    /**
     * @param OrderModel $order
     * @return JsonResponse
     */
    public function orderItems(OrderModel $order) {
        $data =  OrderItemWallet::collection($order->items);
        return $this->responseSuccess($data);
    }

}
