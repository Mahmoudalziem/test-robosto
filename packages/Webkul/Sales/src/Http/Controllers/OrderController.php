<?php

namespace Webkul\Sales\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Webkul\Area\Models\Area;
use Webkul\Sales\Models\Order;
use Webkul\Core\Models\Channel;
use Webkul\Driver\Models\Driver;
use App\Enums\TrackingUserEvents;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Webkul\Product\Models\Product;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Webkul\Area\Models\AreaOpenHour;
use Webkul\Customer\Models\Customer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Redis;
use Webkul\Sales\Models\OrderAddress;
use Webkul\Area\Models\AreaClosedHour;
use Webkul\Sales\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Builder;
use Webkul\Inventory\Models\InventoryArea;
use Webkul\Customer\Models\CustomerAddress;
use Webkul\Sales\Http\Requests\OrderRequest;
use Webkul\Sales\Http\Resources\PaymentsAll;
use Webkul\Core\Services\SendPushNotification;
use Webkul\Sales\Http\Resources\OrderItemsAll;
use Webkul\Sales\Http\Traits\PrepareOrderData;
use Webkul\Sales\Http\Traits\WhiteFridayOffer;
use Webkul\Sales\Repositories\OrderRepository;
use App\Exceptions\PromotionValidationException;
use App\Exceptions\PlaceOrderValidationException;
use Webkul\Core\Services\SendNotificationUsingFCM;
use Webkul\Sales\Http\Resources\OrderDetailsSingle;
use Webkul\Customer\Services\SMS\VictoryLink\SendSMS;
use Webkul\Promotion\Repositories\PromotionRepository;
use Webkul\Core\Http\Controllers\BackendBaseController;
use Webkul\Promotion\Services\PromotionValidation\CheckPromotion;
use Webkul\Promotion\Services\PromotionValidation\Rules\Available;
use Webkul\Promotion\Services\PromotionValidation\Rules\ValidDate;
use Webkul\Promotion\Services\PromotionValidation\Rules\CustomerArea;
use Webkul\Promotion\Services\PromotionValidation\Rules\CustomerTags;
use Webkul\Promotion\Services\PromotionValidation\Rules\VouchersCount;
use Webkul\Promotion\Services\PromotionValidation\Rules\MaxItemQtyAllowed;
use Webkul\Promotion\Services\PromotionValidation\Rules\RedeemsAllowed;
use Webkul\Sales\Services\PlaceOrderValidation\Rules\PaymentMethodRule;
use Webkul\Sales\Services\PlaceOrderValidation\CheckPlaceOrderIsAllowed;
use Webkul\Sales\Services\PlaceOrderValidation\Rules\CheckItemsInAreaRule;
use Webkul\Sales\Services\PlaceOrderValidation\Rules\CheckScheduleTimeRule;
use Webkul\Sales\Services\PlaceOrderValidation\Rules\CheckManyOrdersAtTimeRule;
use Webkul\Promotion\Services\PromotionValidation\Rules\MinimumOrderRequirements;
use Webkul\Promotion\Services\PromotionValidation\Rules\MaxAllowedDevice;

class OrderController extends BackendBaseController {

    use PrepareOrderData,
        WhiteFridayOffer;

    /**
     * OrderRepository object
     *
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * PromotionRepository object
     *
     * @var PromotionRepository
     */
    protected $promotionRepository;

    /**
     * Create a new controller instance.
     *
     * @param OrderRepository $orderRepository
     * @param PromotionRepository $promotionRepository
     * @return void
     */
    public function __construct(OrderRepository $orderRepository, PromotionRepository $promotionRepository) {
        $this->orderRepository = $orderRepository;
        $this->promotionRepository = $promotionRepository;
    }

    /**
     * Create New Order.
     *
     * @param OrderRequest $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function create(OrderRequest $request) {

        $data = $request->only(['items', 'address_id', 'payment_method_id', 'card_id', 'promo_code', 'scheduled_at', 'note']);
        $data['deviceid'] = $request->header('deviceid') ?? null;
        $data = $this->prepareOrderData($data);

        // Check that this address belongs to the authed customer
        if ($data['customer_address']->customer_id != auth('customer')->id()) {
            return $this->responseError(422, __('sales::app.address_not_match_the_customer'));
        }

        if(!in_array($data['area_id'],[1,3])){
            return $this->responseError(422);
        }
        $data = $this->applyWhiteFridayOffer($data);

        if ($data['payment_method_id']==3){
            $this->checkBNPLAvailability($data);
            $data['items'] = $this->addMarginToItems($data['items']);
        }

        // deattach bundle items and merge them to merged itmes
        $data['merged_items'] = $this->getMergedItems($data['items']);

        // Define Order Validation Rules
        $this->applyValidationRules($data);

        // 2- Check Free Shipping Coupon
        $this->checkFreeShippingCoupon($data);

        // 3- Check Promotion Validation
        $this->checkPromotion($data);

        // Update Inventory Area before create the Order
        $this->decreaseInventoryArea($data['merged_items'], $data['area_id']);

        // scheduled order at area closed hours
        $scheduledAt = $this->sheduledOrderAtAreaClosedHours($data);
        if ($scheduledAt) {
            $data['scheduled_at'] = $scheduledAt;
            $successMessage = __('sales::app.success_sheduled_order_at_closed_hours', ['scheduled_at' => Carbon::createFromTimestamp($data['scheduled_at'])->format('Y-m-d H:i')]);
        } else {
            $successMessage = __('sales::app.success_placed_order');
        }


        // In Case all checks Passed, then Create the Order
        $response = $this->orderRepository->create($data);

        return $this->responseSuccess(null, $successMessage);
    }

    /**
     * @param array $data
     *
     * @return void
     */
    private function applyValidationRules(array $data) {
        $rule = new CheckManyOrdersAtTimeRule(auth('customer')->user());
        $rule->setNext(new CheckItemsInAreaRule(auth('customer')->user(), $data['area_id']))
                ->setNext(new CheckScheduleTimeRule($data))
                ->setNext(new PaymentMethodRule($data));

        // 1- Start Order Validation Chaining
        $checkPlaceOrder = new CheckPlaceOrderIsAllowed($data['merged_items']);
        $checkPlaceOrder->setRule($rule);
        $checkPlaceOrder->checkPlaceOrderIsAllowed();
    }

    private function sheduledOrderAtAreaClosedHours($data) {
        Carbon::setLocale("en"); // force to retrieve date in english
        $dayName = Carbon::now()->dayName;
        $currentHour = Carbon::now()->format('H:i') . ':00';
        $currentDate = Carbon::now()->format('Y-m-d');
        //  == test closed hours ==
//          $currentHour = Carbon::parse('16:00:00')->format('H:i:s');
//          $dayName = 'Tuesday';
        //  *************************
        $areaId = isset($data['shadow_area_id']) && !empty($data['shadow_area_id']) ? $data['shadow_area_id'] : $data['area_id'];

        $areaClosedHours = AreaClosedHour::where("area_id", $areaId)
                ->where("from_day", $dayName)
                ->where('from_hour', '<', $currentHour)
                ->where('to_hour', '>=', $currentHour)
                ->first();

        if ($areaClosedHours) {

            $selectedClosedHour = $this->getSchedualedHour($areaClosedHours, $areaClosedHours['to_hour']);
            $selectedClosedDate = $this->getSchedualedDate($dayName, $selectedClosedHour['from_day']);
            // $scheduledString = $selectedClosedDate . ' ' . $selectedClosedHour['to_hour'];
            $scheduledString = Carbon::createFromFormat('Y-m-d H:i:s', $selectedClosedDate . ' ' . $selectedClosedHour['to_hour'], 'Africa/Cairo');
            //dd($currentHour, $dayName, $areaClosedHours['to_hour'], $selectedClosedHour['to_hour'], $selectedClosedDate,$scheduledString);
            return Carbon::parse($scheduledString)->timestamp;
        } else {
            return null;
        }
    }

    private function getSchedualedHour($areaClosedHours, $toHour) {
        $selectedClosedHour = $areaClosedHours;
        // if to_hour != '23:59:59'
        if ($toHour != '23:59:59') {
            return $selectedClosedHour;
        } else {
            $nextClosedHour = AreaClosedHour::where('id', '>', $selectedClosedHour->id)->orderBy('id')->first();
            if ($nextClosedHour['from_hour'] == "00:00:00") {
                return $this->getSchedualedHour($nextClosedHour, $nextClosedHour['to_hour']);
            } else {
                return ["from_day" => $nextClosedHour['from_day'], "to_hour" => "00:00:00"];
            }
        }
    }

    private function getSchedualedDate($currentDayName, $fromDay) {
        if ($currentDayName != $fromDay) {
            return Carbon::parse($fromDay)->toDateString(); // the date of fromDay
        } else {
            return Carbon::now()->format('Y-m-d'); // current date
        }
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
     * @param array $data
     *
     * @return bool
     */
    public function checkPromotion(array $data) {
        $customer = auth('customer')->user();

        if (isset($data['promo_code']) && !empty($data['promo_code'])) {

            // First of all, Check that this is not the first order for the customer
            if ($customer->invitation_applied == 0 && !is_null($customer->invited_by)) {
                if ($customer->activeOrders->isEmpty()) {
                    throw new PromotionValidationException(422, __('customer::app.promoNotWithFirstOrder'));
                }
            }

            // Get the Promotion
            $promotion = $this->promotionRepository->findOneByField('promo_code', $data['promo_code']);
            // implement extra promotion rules
            if (config('robosto.EXTERA_PROMOTOION_RULES')) {
                $promotion = $this->extraPromotionRules($promotion);
            }

            // Define Validation Rules
            $rule = new Available();
            $rule->setNext(new ValidDate())
                    ->setNext(new MaxAllowedDevice($data['deviceid']))
                    ->setNext(new VouchersCount())
                    ->setNext(new MaxItemQtyAllowed($data['items']))
                    ->setNext(new MinimumOrderRequirements($this->totalItemPrice($data['items'],$promotion), $this->sumItemsQty($data['items'])))
                    ->setNext(new CustomerArea($customer, $data['area_id']))
                    ->setNext(new CustomerTags($customer))
                    ->setNext(new RedeemsAllowed($customer));

            // Start Chaining
            $checkPromotion = new CheckPromotion($promotion);
            $checkPromotion->setRule($rule);
            $checkPromotion->checkPromotionIsValid();
        }


        return true;
    }
        /**
     * @param array $data
     *
     * @return bool
     */
    public function checkBNPLAvailability(array $data) {
        $customer = auth('customer')->user();
        $ordersINBNPLConditions = $customer->orders->where('status', Order::STATUS_DELIVERED)->where('created_at','>',Carbon::now()->subMonths(config('robosto.BNPL_AFTER_MONTH'))->toDateString());
        $ordersCount = $ordersINBNPLConditions->count();
        if($ordersCount< config('robosto.BNPL_MINIMUM_ORDERS')){
           throw new PlaceOrderValidationException(411, __('customer::app.minBNPLOrders', ['amount'   =>  config('robosto.BNPL_MINIMUM_ORDERS') - $ordersCount]));
        }
        $ordersTotal = $ordersINBNPLConditions->sum('final_total');
        $maxAmountToUse = ($ordersCount / 10 *  $ordersTotal / $ordersCount) + $customer->wallet;
        $totalPriceAfterBNPL = $this->totalUpdatedBNPLItemPrice($data['items']);
        if($customer->credit_wallet + $totalPriceAfterBNPL > $maxAmountToUse){
            throw new PlaceOrderValidationException(411, __('customer::app.maxBNPLAmountToUse', ['amount'   =>  $maxAmountToUse - $customer->credit_wallet < 0 ? 0 : $maxAmountToUse - $customer->credit_wallet]));
        }
        return true;
    }

            /**
     * @param array $data
     *
     * @return bool
     */
    public function addMarginToItems(array $items) {
        for($i=0;$i<count($items); $i++){
            $items[$i]['margin']= config('robosto.BNPL_INTEREST');
        }
        return $items;
    }

    /** Calculate Payment Summary
     *
     * @param array $data
     * @return float
     */
    private function totalItemPrice(array $items, $promotion = null) {
        $productsFromDB = Product::whereIn('id', array_column($items, 'id'))->get();
        $excludedProductsFromDB = null;
        if (!is_null($promotion) && !is_null($promotion->excluded_from_categories_offer) && $promotion->excluded_from_categories_offer) {
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
        Log::info('total price for promotion:  ' . $total);
        return $total;
    }


    private function totalUpdatedBNPLItemPrice(array $items) {
        $productsFromDB = Product::whereIn('id', array_column($items, 'id'))->get();
        $total = 0;
        $bnplInterest = config('robosto.BNPL_INTEREST');
        foreach ($items as $item) {
            $product = $productsFromDB->where('id', $item['id'])->first();
            $total += $product->tax + (($product->price + ($product->price * $bnplInterest)) * $item['qty']);
        }
        return $total;
    }

    private function extraPromotionRules($promotion) {
        $extraPromotionRules = collect(config('robosto.EXTERA_PROMOTOION_RULES'));
        $extraPromotionCollection = $extraPromotionRules->where('promo_code_id', $promotion->id)->first();
        if ($extraPromotionCollection) {
            $promotion->max_item_qty = isset($extraPromotionCollection['max_item_qty']) ? $extraPromotionCollection['max_item_qty'] : null;
            $promotion->excluded_from_categories_offer = isset($extraPromotionCollection['excluded_from_categories_offer']) ? $extraPromotionCollection['excluded_from_categories_offer'] : null;
            $promotion->max_device_count = isset($extraPromotionCollection['max_device_count'])?$extraPromotionCollection['max_device_count']:null;
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

            // Update Product Quantity using SQL Query
            $updated = DB::update("UPDATE inventory_areas SET total_qty = total_qty  - {$item['qty']} WHERE product_id = {$item['id']} AND area_id = {$area_id}");

            $logText = "Product {$item['id']}  Was Decreased By {$item['qty']} Qty And The Query was => ";
            $queryResult = $updated ? "Done" : "Failed";
            Log::alert($logText . $queryResult);

            // $productInInventoryArea = InventoryArea::where('product_id', $item['id'])
            //         ->where('area_id', $area_id)
            //         ->first();
            // $productInInventoryArea->total_qty = $productInInventoryArea->total_qty - $item['qty'];
            // $productInInventoryArea->save();
        }
    }

    private function getMergedItems(array $items) {

        $data['bundle_items'] = []; // bundle items

        $mergedItems = [];
        $mainItems = [];
        $newBundleItems = [];

        foreach ($items as $item) {

            $product = Product::find($item['id']);

            if ($product->bundle_id) {
                $bundleItems = $product->bundle->items;
                foreach ($bundleItems as $bundleItem) {
                    $data['bundle_items'][$product->id][] = ['id' => $bundleItem['product_id'], 'qty' => $bundleItem['quantity'] * $item['qty']];
                }
            } else {
                $mainItems[$product->id] = ['id' => $item['id'], 'qty' => $item['qty']];
            }
        }

        Log::info('bundle_items');
        Log::info($data['bundle_items']);
        $itemBundleQty[] = 0;

        foreach ($data['bundle_items'] as $key => $items) {

            foreach ($items as $item) {
                // && !array_search($item['id'], array_keys($newBundleItems[$item['id']]))
                if (!isset($newBundleItems[$item['id']])) {

                    $itemBundleQty[$item['id']] = !isset($itemBundleQty[$item['id']]) ? $item['qty'] : $itemBundleQty[$item['id']];
                    Log::info('$first Qty  of ' . $item['id'] . ' is ' . $itemBundleQty[$item['id']]);
                    $newBundleItems[$item['id']] = ['id' => $item['id'], 'qty' => $itemBundleQty[$item['id']]];
                } else {
                    $itemBundleQty[$item['id']] = $itemBundleQty[$item['id']] + $item['qty'];
                    unset($newBundleItems[$item['id']]);

                    Log::info('$oldQty  of ' . $item['id'] . ' is ' . $itemBundleQty[$item['id']]);
                    $newBundleItems[$item['id']] = ['id' => $item['id'], 'qty' => ($itemBundleQty[$item['id']] )];
                    Log::info('$new  of ' . $item['id'] . ' is ' . $newBundleItems[$item['id']]['qty']);
                }
            }
            Log::info("____" . $key . "____");
        }
        Log::info(ksort($mainItems));
        Log::info(ksort($newBundleItems));
        $mergedItemQty[] = 0;

        if (count($mainItems) > 0 && count($newBundleItems) == 0) {
            $mergedItems = $mainItems;
        } elseif (count($mainItems) == 0 && count($newBundleItems) > 0) {
            $mergedItems = $newBundleItems;
        } elseif (count($mainItems) > 0 && count($newBundleItems) > 0) {
            $mergedItems = $mainItems;
            foreach ($mergedItems as $key => $item) {
                $mergedItemQty[$item['id']] = $item['qty'];
                foreach ($newBundleItems as $bundleItem) {

                    if (!isset($mergedItems[$bundleItem['id']])) {

                        $mergedItemQty[$bundleItem['id']] = !isset($mergedItemQty[$bundleItem['id']]) ? $bundleItem['qty'] : $mergedItemQty[$bundleItem['id']];
                        //   dd($item['id'],$bundleItem['id']);
                        Log::info('$first Qty  of ' . $bundleItem['id'] . ' is ' . $mergedItemQty[$bundleItem['id']]);
                        $mergedItems[$bundleItem['id']] = ['id' => $bundleItem['id'], 'qty' => $mergedItemQty[$bundleItem['id']]];
                    } else {

                        $mergedItemQty[$bundleItem['id']] = $mergedItemQty[$item['id']] + $bundleItem['qty'];

                        unset($mergedItems[$item['id']]);
                        Log::info('$oldQty  of ' . $bundleItem['id'] . ' is ' . $mergedItemQty[$item['id']]);
                        $mergedItems[$item['id']] = ['id' => $item['id'], 'qty' => ($mergedItemQty[$item['id']] )];
                    }
                }

                Log::info("____" . $key . "____");
            }
        }
        ksort($mergedItems);

        return $mergedItems;
    }

    private function deattachBunldeItems(array $bundleItems) {
        $data['appended_Items'] = [];
        foreach ($bundleItems as $bundleItem) {
            $data['bundle_Items'][] = ['id' => $bundleItem['product_id'], 'qty' => $bundleItem['quantity'] * $item['qty']];
        }
    }

    private function deattachItems(array $bundleItems) {
        $data['appended_Items'] = [];
        foreach ($bundleItems as $bundleItem) {
            $data['bundle_Items'][] = ['id' => $bundleItem['product_id'], 'qty' => $bundleItem['quantity'] * $item['qty']];
        }
    }

    /**
     * Create New Scheduled Order.
     *
     * @param OrderRequest $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function createSchedule(OrderRequest $request) {
        $data = $request->only(['items', 'address_id', 'note', 'scheduled_at', 'payment_method_id']);
        $data['area_id'] = $request->header('area');
        $data['customer_id'] = auth('customer')->id();
        $data['channel_id'] = Channel::MOBILE_APP;

        $order = $this->orderRepository->create($data);

        return $this->responseSuccess($order, 'Success');
    }

    /**
     * Show the specified order.
     *
     * @param $id
     * @return JsonResponse
     */
    public function show($id) {
        $order = $this->orderRepository->findOrFail($id);

        Event::dispatch('app-orders.show', $order);

        $data = new OrderDetailsSingle($order);

        return $this->responseSuccess($data);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function orderActive(Request $request) {
        $customer = auth('customer')->user();
        $query = $customer->activeOrders();

        $perPage = $request->has('per_page') ? (int) $request->per_page : null;
        $pagination = $query->paginate($perPage);
        $pagination->appends([
            'per_page' => $request->per_page
        ]);
        $data = new OrderItemsAll($pagination);

        return $this->responsePaginatedSuccess($data, null, $request);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function previousOrders(Request $request) {

        $customer = auth('customer')->user();
        $query = $customer->previousOrders();

        $perPage = $request->has('per_page') ? (int) $request->per_page : null;
        $pagination = $query->paginate($perPage);
        $pagination->appends([
            'per_page' => $request->per_page
        ]);
        $data = new OrderItemsAll($pagination);
        return $this->responsePaginatedSuccess($data, null, $request);
    }

    /**
     * Show the specified order.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function getPayments() {
        $payments = PaymentMethod::active()->get();

        $data = new PaymentsAll($payments);

        return $this->responseSuccess($data);
    }

    /**
     * @param Request $request
     *
     * @return bool
     */
    public function orderConfirmationCallback(Request $request) {

        $data = $request->only(['order_number', 'order_status', 'call_status', 'customer_number']);

        if (isset($data['call_status']) && $data['call_status'] == 'answered') {

            $order = Order::find($data['order_number']);
            $driver = Driver::where('phone_work', ltrim($data['customer_number'], "2"))->first();

            if ($order && $driver && $order->status == Order::STATUS_PENDING) {
                $newData['action'] = 'confirm';

                // Call the function that Handle Driver Response
                $this->orderRepository->driverNewOrderResponse($order, $driver, $newData);

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
        }

        return true;
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function initiateCall(Request $request) {
        $request->validate([
            'phone_number' => 'required|numeric|regex:/(2)(0)(1)[0-9]{8}/',
        ]);

        $from = ltrim($request->phone_number, "2");

        $to = $this->getCaleePhoneNumber($from);

        if ($to) {
            return response()->json(['status' => 200, 'phone_number' => $to, 'from' => $from]);
        }

        return response()->json(['status' => 400, 'phone_number' => null, 'from' => $from]);
    }

    /**
     * @param string $from
     *
     * @return string|bool
     */
    private function getCaleePhoneNumber(string $from) {
        Log::info("From -> " . $from);
        // First Check if the caller is the driver, then get the customer phone
        $driver = Driver::where('phone_work', $from)->first();
        if ($driver) {
            Log::info("Driver is the Caller");
            return $this->getCustomerPhoneFroTheDriver($driver->id);
        }

        Log::info("Customer is the Caller");
        // Else, if the caller is the customer, then get the driver phone
        return $this->getDriverPhoneForTheCustomer($from);

        $order = $this->dbQuery($from);

        if ($order) {

            if ($from == $order->customer_phone) {
                return '2' . $order->driver_phone;
            }
            return '2' . $order->customer_phone;
        }

        return false;
    }

    /**
     * @param int $driverId
     *
     * @return string|bool
     */
    private function getCustomerPhoneFroTheDriver(int $driverId) {
        if (!Cache::has("driver_{$driverId}_current_order")) {
            return false;
        }

        $orderID = Cache::get("driver_{$driverId}_current_order");

        $orderAddress = OrderAddress::where('order_id', $orderID)->whereHas('order', function (Builder $query) {
                    $query->whereIn('orders.status', [Order::STATUS_ON_THE_WAY, Order::STATUS_AT_PLACE]);
                })->first();

        if ($orderAddress) {
            return '2' . $orderAddress->phone;
        }
        return false;
    }

    /**
     * @param string $from
     *
     * @return string|bool
     */
    private function getDriverPhoneForTheCustomer(string $from) {
        // Get the Customer phone
        $orderAddress = OrderAddress::where('phone', $from)->whereHas('order', function (Builder $query) {
                    $query->whereIn('orders.status', [Order::STATUS_ON_THE_WAY, Order::STATUS_AT_PLACE]);
                })->first();

        if ($orderAddress) {
            return '2' . $orderAddress->order->driver->phone_work;
        }

        return false;
    }

    /**
     * @param string $from
     *
     * @return mixed
     */
    private function dbQuery(string $from) {
        return DB::table('orders')
                        ->join('drivers', 'orders.driver_id', '=', 'drivers.id')
                        ->join('customers', 'orders.customer_id', '=', 'customers.id')
                        ->join('order_address', 'orders.id', '=', 'order_address.order_id')
                        ->whereIn('orders.status', [Order::STATUS_ON_THE_WAY, Order::STATUS_AT_PLACE])
                        ->where(function ($query) use ($from) {
                            $query
                            ->where('drivers.phone_work', $from)
                            ->orWhere('order_address.phone', $from)
                            ->orWhere('customers.phone', $from);
                        })
                        ->select('drivers.phone_work AS driver_phone', 'customers.phone AS customer_phone')
                        ->first();
    }

    public function callCallback(Request $request) {
        Log::info("Call Callback Request");
        Log::info($request->all());
        return response()->json();
    }

    public function testNotification(Request $request) {
        //        Redis::publish('open.channel', json_encode(['ahmed' =>  'mohsen']));
        //        Redis::publish('driver.received.order', json_encode(['ahmed' =>  'mohsen']));
        //        return ['Redis Sent'];


        $n = (new SendNotificationUsingFCM())->sendNotification(
                [
                    $request->token
                ],
                [
                    'title' => 'Notification title',
                    'body' => 'Order Cancelled by Hdeawy',
                    'data' => ['model_id' => 75, 'direct_to' => 'order-profile', 'key' => $request->has('key') ? $request->key : 'new_order']
                ]
        );

        dd($n);

        // SendPushNotification::send(
        //     [$request->token],
        //     [
        //         'title' => 'Two Notification title',
        //         'body' => 'Order Cancelled by Hdeawy',
        //         'data' => ['model_id' => 75, 'direct_to'   =>  'order-profile', 'key' => 'ready_to_pickup']
        //     ]
        // );

        return 'done';
    }

}
