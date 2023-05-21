<?php

namespace Webkul\Sales\Repositories;

use Carbon\Carbon;
use App\Jobs\CallSysAdmins;
use Webkul\Core\Models\Tag;
use App\Jobs\SetCustomerTag;
use Webkul\Area\Models\Area;
use App\Jobs\CallCustomerJob;
use App\Jobs\CheckOrderItems;
use App\Jobs\OrderProcessing;
use App\Jobs\RoboDistanceJob;
use App\Jobs\PayViaCreditCard;
use App\Jobs\SendOrderToDriver;
use Webkul\Core\Models\Channel;
use App\Jobs\GetAndStoreDrivers;
use App\Jobs\HandleOneOrderTags;
use Webkul\Driver\Models\Driver;
use App\Enums\TrackingUserEvents;
use Webkul\Sales\Contracts\Order;
use Illuminate\Support\Facades\DB;
use Webkul\Product\Models\Product;
use Illuminate\Support\Facades\Log;
use App\Jobs\CustomerCancelledOrder;
use App\Jobs\DriverAcceptedNewOrder;
use App\Jobs\DriverRejectedNewOrder;
use Illuminate\Support\Facades\Http;
use Webkul\Bundle\Models\BundleItem;
use Webkul\Core\Eloquent\Repository;
use Webkul\Customer\Models\Customer;
use Webkul\Customer\Models\CustomerSmsSetting;
use App\Jobs\AssignNewOrdersToDriver;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Redis;
use Webkul\Sales\Models\OrderItemSku;
use Webkul\Sales\Models\OrderPayment;
use Webkul\Collector\Models\Collector;
use Webkul\Inventory\Models\Warehouse;
use Webkul\Promotion\Models\Promotion;
use Webkul\Sales\Models\PaymentMethod;
use App\Jobs\AcceptOrderByDefaultDriver;
use App\Jobs\SendNotificationToCustomer;
use Webkul\Sales\Models\OrderLogsActual;
use Illuminate\Database\Eloquent\Builder;
use App\Jobs\CustomerAcceptedOrderChanges;
use Illuminate\Container\Container as App;
use Webkul\Inventory\Models\InventoryArea;
use Webkul\Customer\Models\CustomerAddress;
use Illuminate\Database\Eloquent\Collection;
use Webkul\Sales\Models\Order as OrderModel;
use Webkul\Sales\Models\OrderDriverDispatch;
use App\Jobs\HandleCustomerInvitationInOrder;
use Webkul\Inventory\Models\InventoryProduct;
use Webkul\Core\Services\SendPushNotification;
use Webkul\Inventory\Models\InventoryWarehouse;
use App\Exceptions\PromotionValidationException;
use App\Jobs\AcceptOrderByDefaultDriverForShippingOrders;
use App\Jobs\AcceptPickupOrderForDriverAndCollector;
use App\Jobs\AcceptShippingOrderForDriverAndCollector;
use App\Jobs\ShippmentOrderRouter;
use Webkul\Sales\Repositories\Traits\OrderPayViaCC;
use \Webkul\Customer\Http\Controllers\Auth\SMSTrait;
use Webkul\Customer\Repositories\CustomerRepository;
use Webkul\Customer\Services\SMS\VictoryLink\SendSMS;
use Webkul\Promotion\Repositories\PromotionRepository;
use Webkul\Sales\Repositories\Traits\OrderNotifications;
use Webkul\Sales\Repositories\Traits\CollectorRoundRobin;
use LaravelFCM\Message\Exceptions\InvalidOptionsException;
use Webkul\Promotion\Services\ApplyPromotion\ApplyPromotion;
use Webkul\Core\Services\LocationService\Distance\DistanceService;
use Webkul\Sales\Services\NewOrderFilters\CheckItemsAvailableInArea;
use Webkul\Sales\Services\NewOrderFilters\CheckItemsAvailableInAreaWarehouses;
use Webkul\Shipping\Models\ShippmentLogs;

class OrderRepository extends Repository {

    use OrderNotifications,
        OrderPayViaCC,
        CollectorRoundRobin,
        SMSTrait;

    /**
     * OrderItemRepository object
     *
     * @var OrderItemRepository
     */
    protected $orderItemRepository;
    protected $notEnoughToOrder = [];
    protected $outOfStockToOrder = [];

    /**
     * PromotionRepository object
     *
     * @var PromotionRepository
     */
    protected $promotionRepository;

    /**
     * CustomerRepository object
     *
     * @var CustomerRepository
     */
    protected $customerRepository;

    /**
     * Create a new repository instance.
     *
     * @param OrderItemRepository $orderItemRepository
     * @param PromotionRepository $promotionRepository
     * @param CustomerRepository $customerRepository
     * @param App $app
     */
    public function __construct(
            OrderItemRepository $orderItemRepository,
            PromotionRepository $promotionRepository,
            CustomerRepository $customerRepository,
            App $app
    ) {
        $this->orderItemRepository = $orderItemRepository;
        $this->promotionRepository = $promotionRepository;
        $this->customerRepository = $customerRepository;
        parent::__construct($app);
    }

    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model() {
        return Order::class;
    }

    /**
     * @param array $data
     * @return Order
     * @throws \Exception
     */
    public function create(array $data) {
        $data['increment_id'] = $this->generateIncrementId();
        $promotion = null;

        DB::beginTransaction();
        try {
            // First Create Order
            $data['status'] = OrderModel::STATUS_PENDING;
            $order = $this->model->create($data);

            // Save Order Address
            $this->createOrderAddress($order, $data['customer_address']);

            // Save Order Items
            if (isset($data['promo_code']) && !empty($data['promo_code'])) {
                // Get Promotion
                $promotion = Promotion::where('promo_code', $data['promo_code'])->first();
                $this->saveOrderItemsWithPromotion($order, $data['items'], $promotion);

                //Save promotion deviceid for Customer
                if($order->channel_id == Channel::MOBILE_APP){
                    $this->saveCustomerDeviceId($order, $data, $promotion);
                }

            } else {
                $this->saveOrderItems($order, $data['items']);
            }

            // Update Master Order Price and Quantities
            $this->updateOrderWithCalculations($order, $data, $promotion);

            // Save Payment Method
            $this->saveOrderPaymentMethod($order, $data);

            //adjust for BNPL
            if($data['payment_method_id']==3){
                $this->executeBNPLRequest($order);
            }

            // Update CUstomer Total Orders
            $this->updateCustomerTotalOrders($order);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        // Fire Events
        Event::dispatch('app.order.placed', $order);
        Event::dispatch('order.actual_logs', [$order, OrderLogsActual::ORDER_PLACED]);

        logOrderActionsInCache($order->id, 'order_placed');

        // In Case, Create order from Mobile App
        if ($order->status == OrderModel::STATUS_PENDING && $order->channel_id == Channel::MOBILE_APP) {

            logOrderActionsInCache($order->id, 'order_from_mobile');
            $trackData = ['server' => request()->server(), 'order_id' => $order->id];
            Event::dispatch('tracking.user.event', [TrackingUserEvents::PURCHASE, auth('customer')->user(), $trackData]);

            // in case, order scheduled or Not
            if (isset($data['scheduled_at']) && !empty($data['scheduled_at']) && $data['scheduled_at'] != 0) {
                // Schedule The order and send notification
                $this->orderScheduled($order, $data);

                return $order;
            }

            OrderProcessing::dispatch($order);

            return $order;
        }

        logOrderActionsInCache($order->id, 'order_from_portal');
        return $order;
    }


    public function createShippingOrder(array $data , bool $pickup = false) {
        $data['increment_id'] = $this->generateIncrementId();
        Log::info($data);
        DB::beginTransaction();
        try {
            $data['status'] = OrderModel::STATUS_PENDING;
            $order = $this->model->create($data);
            Event::dispatch('app.order.placed', $order);
            Event::dispatch('order.actual_logs', [$order, OrderLogsActual::ORDER_PLACED]);
            logOrderActionsInCache($order->id, 'order_placed');
            if(isset($data["warehouse_address"])){
                $this->createOrderAddressFromWarehouse($order, $data['warehouse_address']);
            }

            if(isset($data["customer_address"])){
               $this->createOrderAddress($order, $data['customer_address']);
            }
            $product = Product::find(config('robosto.SHIPPING_PRODUCT'));
            $totalPrice = $data["final_total"];
            $itemsCount= $data["items_count"];
            $itemData = [
                'product_id' => $product->id,
                'bundle_id' => $product->bundle_id ?? null,
                'order_id' => $order->id,
                'shelve_position' => $product->shelve ? $product->shelve->position : null,
                'shelve_name' => $product->shelve ? $product->shelve->name . $product->shelve->row : null,
                'weight' => $product->weight,
                'qty_ordered' => $itemsCount,
                'qty_shipped' => $itemsCount,
                'qty_invoiced' => $itemsCount,
                'base_price' => $product->price,
                'base_total' => $totalPrice,
                'base_total_invoiced' => $totalPrice,
                'price' => $product->price,
                'total' => $totalPrice,
                'total_invoiced' => $totalPrice,
            ];
            // Save New Item
            $this->orderItemRepository->create($itemData);
            if (isset($data['scheduled_at']) && !empty($data['scheduled_at']) && $data['scheduled_at'] != 0) {
                $order->scheduled_at = $data['scheduled_at'];
                $order->status = OrderModel::STATUS_SCHEDULED;
                $order->final_total = $data["final_total"];
                $order->sub_total = $data["final_total"];
                $order->save();
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
        // if($pickup){
        //     AcceptShippingOrderForDriverAndCollector::dispatch($order, $pickup);
        // }
    }
    /**
     * @return int
     */
    public function generateIncrementId() {
        $latestOrder = $this->model->latest()->first();
        if ($latestOrder) {
            if (strlen((string) $latestOrder->increment_id) > 4) {
                $incrementId = (substr($latestOrder->increment_id, 0, - 3)) + 1 . rand(100, 999);
            } else {
                $incrementId = ( $latestOrder->increment_id + 1 ) . rand(100, 999);
            }
        } else {
            $incrementId = 1;
        }

        return $incrementId;
    }

    /**
     * @param OrderModel $order
     * @param CustomerAddress $address
     *
     * @return [type]
     */
    public function createOrderAddress(OrderModel $order, CustomerAddress $address) {
        logOrderActionsInCache($order->id, 'create_order_address');

        $address = $order->address()->create([
            'name' => $address->name,
            'address' => $address->address,
            'floor_no' => $address->floor_no,
            'apartment_no' => $address->apartment_no,
            'building_no' => $address->building_no,
            'landmark' => $address->landmark,
            'latitude' => $address->latitude,
            'longitude' => $address->longitude,
            'phone' => $address->phone
        ]);

        return $address;
    }
    public function createOrderAddressFromWarehouse(OrderModel $order, $address) {
        $address = $order->address()->create([
            'name' => $address->contact_name,
            'address' => $address->address,
            'latitude' => $address->latitude,
            'longitude' => $address->longitude,
            'phone' => $address->contact_phone
        ]);

        return $address;
    }
    /**
     * @param OrderModel $order
     * @param array $data
     *
     * @return void
     */
    public function orderScheduled(OrderModel $order, array $data) {
        logOrderActionsInCache($order->id, 'order_scheduled');

        $order->scheduled_at = Carbon::createFromTimestamp($data['scheduled_at'])->format('Y-m-d H:i:s');
        $order->status = OrderModel::STATUS_SCHEDULED;
        $order->save();

        $day = Carbon::createFromTimestamp($data['scheduled_at'])->format('d-m');
        $hour = Carbon::createFromTimestamp($data['scheduled_at'])->format('h:i A');
        $dataToCustomer = [
            'title' => "جدولة الطلب",
            'body' => "تم جدولة طلبك في " . $day . " الساعة " . $hour,
            'details' => [
                'order_id' => $order->id
            ]
        ];

        SendNotificationToCustomer::dispatch($order, $dataToCustomer);
    }

    /**
     * @param OrderModel $order
     *
     * @return void
     */
    public function updateCustomerTotalOrders(OrderModel $order) {
        $customer = $order->customer;

        $customer->total_orders += 1;
        $customer->save();
    }

    /**
     * @param OrderModel $order
     * @param CustomerAddress $address
     *
     * @return [type]
     */
    public function saveCustomerDeviceId(OrderModel $order, array $data, Promotion $promotion = null) {
        if ($promotion && isset($data['deviceid'])) {
            logOrderActionsInCache($order->id, 'create_create_deviceid');

            $promotion->voidDevice()->create([
                'order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'deviceid' => $data['deviceid']??null
            ]);

            return $promotion;
        }
    }

    /**
     * @param OrderModel $order
     * @param array $items
     * @return mixed
     */
    public function saveOrderItems(OrderModel $order, $items) {
        $itemsIDs = array_column($items, 'id');
        $productsFromDB = Product::whereIn('id', $itemsIDs)->get();

        // Save order Items
        foreach ($items as $item) {
            $margin = isset($item['margin']) ? $item['margin'] : 0;
            $product = $productsFromDB->find($item['id']);
            $product->price = $product->price + ($product->price * $margin );
            // Collect Item Data
            $totalPrice = $item['qty'] * $product->price;
            $itemData = [
                'product_id' => $product->id,
                'bundle_id' => $product->bundle_id ?? null,
                'order_id' => $order->id,
                'shelve_position' => $product->shelve ? $product->shelve->position : null,
                'shelve_name' => $product->shelve ? $product->shelve->name . $product->shelve->row : null,
                'weight' => $product->weight,
                'qty_ordered' => $item['qty'],
                'qty_shipped' => $item['qty'],
                'qty_invoiced' => $item['qty'],
                'base_price' => $product->price,
                'base_total' => $totalPrice,
                'base_total_invoiced' => $totalPrice,
                'price' => $product->price,
                'total' => $totalPrice,
                'total_invoiced' => $totalPrice,
            ];

            $itemData = $this->handleInvitationGiftInOrderItems($order, $itemData);

            // Save New Item
            $this->orderItemRepository->create($itemData);
        }
    }

    /**
     * @param OrderModel $order
     * @param array $items
     * @return mixed
     */
    public function saveOrderItemsWithPromotion(OrderModel $order, array $items, Promotion $promotion, $updateOrder = false) {
        // Run this code if the promotion is new not in the order before
        if (!$updateOrder) {
            // First Update Promotion
            $this->promotionRepository->updateUsedPromotion($promotion);
            // Update Redeems for the customer
            $this->customerRepository->updateCustomerPromotionRedeems($order->customer, $promotion->id);
        }

        // Get All Items From DB (Products)
        $itemsIDs = array_column($items, 'id');
        $productsFromDB = Product::whereIn('id', $itemsIDs)->get();

        // Apply Promotion On Items
        $applyPromotion = new ApplyPromotion($promotion, $items);
        $products = $applyPromotion->apply();

        // Save Order Items that has Discounted Price
        if (!empty($products['discounted_items'])) {
            $this->saveDiscountedItems($order, $products['discounted_items'], $productsFromDB, $promotion);
        }

        // Save Order Items that has not Discounted Price
        if (!empty($products['except_items'])) {
            $this->saveOrderItems($order, $products['except_items']);
        }
    }

    /**
     * @param OrderModel $order
     * @param array $items
     * @param mixed $products
     * @param Promotion $promotion
     * @return mixed
     */
    public function saveDiscountedItems(OrderModel $order, array $items, $products, Promotion $promotion) {
        // Save Order Items that has Discounted Price
        foreach ($items as $item) {
            $product = $products->find($item['id']);

            // Collect Item Data
            $totalPrice = $item['total_price'];
            $discountedTotalPrice = $item['total_discounted_price'];
            $discountAmount = $promotion->discount_value;
            if ($promotion->discount_type == Promotion::DISCOUNT_TYPE_VALUE) {
                $discountAmount = $totalPrice - $discountedTotalPrice;
            }
            $itemData = [
                'product_id' => $product->id,
                'bundle_id' => $product->bundle_id ?? null,
                'order_id' => $order->id,
                'shelve_position' => $product->shelve ? $product->shelve->position : null,
                'shelve_name' => $product->shelve ? $product->shelve->name . $product->shelve->row : null,
                'weight' => $product->weight,
                'qty_ordered' => $item['qty'],
                'qty_shipped' => $item['qty'],
                'qty_invoiced' => $item['qty'],
                'base_price' => $item['price'],
                'price' => $item['discounted_price'],
                'base_total' => $totalPrice,
                'total' => $discountedTotalPrice,
                'total_invoiced' => $discountedTotalPrice,
                'base_total_invoiced' => $discountedTotalPrice,
                'discount_type' => $promotion->discount_type,
                'discount_amount' => $discountAmount
            ];
            // Save New Item
            $this->orderItemRepository->create($itemData);
        }
    }

    /**
     * @param OrderModel $order
     * @param array $itemData
     *
     * @return array
     */
    public function handleInvitationGiftInOrderItems(OrderModel $order, array $itemData) {
        $checkProductInExcludedCategories = $this->checkInExcludedCategories($itemData['product_id']);
        // If this Order is the First Order and the customer was used Referral Code
        $customer = $order->customer;
        if ($customer->invitation_applied == 0 && !is_null($customer->invited_by) && $checkProductInExcludedCategories) {

            // if this customer already toked 10% discount
            $customerActiveOrders = $customer->activeOrders->where('id', '!=', $order->id);
            if ($customerActiveOrders->isEmpty()) {

                // Apply 10% Discount on the Order Total
                $percentage = config('robosto.ORDER_INVITE_CODE_GIFT'); // 15%
                $newPrice = $itemData['price'] - (($percentage / 100) * $itemData['price']);
                $newTotalPrice = $itemData['total'] - (($percentage / 100) * $itemData['total']);
                $itemData['price'] = $newPrice;
                $itemData['total'] = $newTotalPrice;
                $itemData['total_invoiced'] = $newTotalPrice;
                $itemData['discount_type'] = Promotion::DISCOUNT_TYPE_PERCENT;
                $itemData['discount_amount'] = config('robosto.ORDER_INVITE_CODE_GIFT');
            }
        }

        return $itemData;
    }

    /**
     * @param int $productId
     *
     * @return bool
     */
    private function checkInExcludedCategories(int $productId) {
        $excludedCategories = config('robosto.EXCLUDED_CATEGORIES');

        $check = Product::where('id', $productId)->whereHas('subCategories', function (Builder $query) use ($excludedCategories) {
                    $query->whereHas('parentCategories', function (Builder $query) use ($excludedCategories) {
                        $query->whereIn('category_id', $excludedCategories);
                    });
                })->first();

        if ($check) {
            return false;
        }

        return true;
    }

    /**
     * @param OrderModel $order
     * @param array $data
     * @param Promotion $promotion
     * @param bool $update
     *
     * @return mixed
     */
    public function updateOrderWithCalculations(OrderModel $order, array $data, Promotion $promotion = null, bool $update = false) {
        $order = OrderModel::find($order->id);
        // Load order Items
        $orderItems = $order->items;
        $customer = $order->customer;
        $customerWallet = $customer->wallet;
        $total = $orderItems->sum('total');
        $deliverFees = $this->handleDeliveryFees($orderItems->sum('base_total'));
        $deliverFees = $this->handleFreeShippingCoupon($order, $data, $deliverFees);
        $baseFinalTotal = $total + $deliverFees + config('robosto.DEFAULT_TAX');

        // If Update, then check different in prices if order paid via CC
        if ($update) {
            $this->checkPaidOrderViaCCAfterUpdated($order, $baseFinalTotal);
        }

        // Update Order Price and total qty
        $this->saveExtraOrderData($order, $orderItems, $deliverFees, $baseFinalTotal);

        // Handle Promotion Discount
        $this->handlePromotionDiscount($order, $promotion);

        // If this Order is the First Order and the customer was used Referral Code
        $this->handleCustomerInvitationApplied($order, $customer);

        if ($baseFinalTotal <= 0) {
            // Save the order and return
            $order->save();

            return true;
        }

        // Save Customer Balance
        $this->saveCustomerBalance($order, $customer);

        // Handle Customer Wallet
        $this->handleCustomerWalletWithNewOrder($order, $customer);

        // Get Customer Wallet & Apply Customer Wallet in Order total
        $order->final_total = $order->final_total - $customerWallet;
        if ($order->final_total <= 0) {
            $order->final_total = 0;
        }
        // Finall Save the Order
        $order->save();
    }

    /**
     * @param OrderModel $order
     * @param float $baseFinalTotal
     *
     * @return void
     */
    private function checkPaidOrderViaCCAfterUpdated(OrderModel $order, float $baseFinalTotal) {
        Log::info("Check PaidOrder Via CC");
        // If the Order is Paid via CC and  the new price is less than the order price, return the remaining into customer wallet
        if ($order->is_paid == OrderModel::ORDER_PAID && $order->paid_type == OrderModel::PAID_TYPE_CC && $order->final_total > $baseFinalTotal) {
            Log::info("Yes The Order was Paid Via CC and Updated");
            $diff = $order->final_total - $baseFinalTotal;
            $this->addMoneyToCustomerWallet($order, $diff);
        }
    }

    /**
     * @param OrderModel $order
     * @param Collection $orderItems
     * @param float $deliverFees
     * @param float $baseFinalTotal
     *
     * @return void
     */
    private function saveExtraOrderData(OrderModel $order, Collection $orderItems, float $deliverFees, float $baseFinalTotal) {
        Log::info("Save Extra Order Data");

        $order->items_count = count($orderItems);
        $order->items_shipped_count = count($orderItems);
        $order->items_qty_ordered = $orderItems->sum('qty_ordered');
        $order->items_qty_shipped = $orderItems->sum('qty_ordered');
        $order->sub_total = $orderItems->sum('base_total');
        $order->delivery_chargs = $deliverFees;
        $order->tax_amount = config('robosto.DEFAULT_TAX');
        $order->final_total = $baseFinalTotal;

    }

    /**
     * @param OrderModel $order
     * @param Promotion|null $promotion
     *
     * @return void
     */
    private function handlePromotionDiscount(OrderModel $order, ?Promotion $promotion) {
        Log::info("Handle Promotion Discount");

        if (!is_null($promotion)) {
            $order->coupon_code = $promotion->promo_code;
            $order->discount_type = $promotion->discount_type;
            $order->discount = $promotion->discount_value;
            $order->promotion_id = $promotion->id;
        }
    }

    /**
     * @param OrderModel $order
     * @param Customer $cutomer
     *
     * @return void
     */
    private function handleCustomerInvitationApplied(OrderModel $order, Customer $customer) {
        Log::info("Handle Customer Invitaion Applied");

        if ($customer->invitation_applied == 0 && !is_null($customer->invited_by)) {

            // if this customer already toked 10% discount
            $customerActiveOrders = $customer->activeOrders->where('id', '!=', $order->id);
            if ($customerActiveOrders->isEmpty()) {

                $order->coupon_code = $customer->invitedBy->referral_code;
                $order->discount_type = Promotion::DISCOUNT_TYPE_PERCENT;
                $order->discount = config('robosto.ORDER_INVITE_CODE_GIFT');
            }
        }
    }

    /**
     * @param float $total
     *
     * @return float
     */
    private function handleDeliveryFees(float $total) {
        $deliverFees = config('robosto.DELIVERY_CHARGS');

        if ($total >= config('robosto.MINIMUM_ORDER_AMOUNT')) {
            return 0;
        }
        return $deliverFees;
    }

    /**
     * @param OrderModel $order
     * @param array $data
     * @param float $fees
     *
     * @return float
     */
    private function handleFreeShippingCoupon(OrderModel $order, array $data, float $fees) {
        if (isset($data['free_shipping']) && $data['free_shipping'] == true) {
            $promotion = Promotion::where('promo_code', config('robosto.FREE_SHIPPING_COUPON'))->first();
            if ($promotion) {
                $customer = Customer::find($data['customer_id']);

                // Check Customer Tags Exist Promotion Tags
                $customerTags = $customer->tags()->pluck('tags.id')->toArray();
                $promotionTags = $promotion->tags()->pluck('tags.id')->toArray();

                // Check Customer has at least Tag in Promotion Tags
                if (count(array_intersect($customerTags, $promotionTags)) == 0) {
                    Log::info('Free Shipping Coupon Not Valid For Tag');
                    throw new PromotionValidationException(406, __('customer::app.notValidPromoCode'));
                }

                $order->coupon_code = config('robosto.FREE_SHIPPING_COUPON');
                $order->promotion_id = $promotion->id;
                $order->save();

                return 0;
            }
        }

        return $fees;
    }

    /**
     * @param OrderModel $order
     * @param Customer $customer
     *
     * @return void
     */
    public function saveCustomerBalance(OrderModel $order, Customer $customer) {
        Log::info("Save Customer Balance");
        $orderBaseTotal = $order->final_total;
        $customerWallet = $customer->wallet;

        // in case ( Wallet = 40, OT = 25 )
        if ($customerWallet > $orderBaseTotal) {
            // Balance will be Order-Total in Minus ( CB = -25 ), then Wallet = 15  =>  in refund|cancel wallet will be ( 15 - (-25) = 40  )
            $order->customer_balance = (-$orderBaseTotal);
        } else {
            // in case ( Wallet = 35, OT = 55 )
            // Balance will be Wallet in Minus ( CB = -35 ), then Wallet = 0  =>  in refund|cancel wallet will be ( 0 - (-35) = 35  )
            $order->customer_balance = (-$customerWallet);
        }
    }

    /**
     * @param OrderModel $order
     * @param Customer $customer
     *
     * @return void
     */
    public function handleCustomerWalletWithNewOrder(OrderModel $order, Customer $customer) {

        Log::info("Save Customer Wallet");

        $orderBaseTotal = $order->final_total;
        $customerWallet = $customer->wallet;

        if ($customerWallet != 0) {

            Log::info("THe Customer has money in his Wallet");

            if ($customerWallet >= $orderBaseTotal) {
                // Decrease Order price from Wallet
                $customer->subtractMoney($orderBaseTotal, $order->id, $order->increment_id);
            } else {
                // Adjust Customer Wallet
                if ($customerWallet > 0) {
                    // Minus amount from customer wallet
                    $this->subtractMoneyFromCustomerWallet($order, abs($customerWallet));
                } else {
                    // Plus amount to customer wallet
                    $this->addMoneyToCustomerWallet($order, abs($customerWallet));
                }
            }
        }
    }

    /**
     * @param OrderModel $order
     * @param array $data
     * @return mixed $address
     */
    public function saveOrderPaymentMethod(OrderModel $order, $data) {
        logOrderActionsInCache($order->id, 'save_order_payment');

        $payment = PaymentMethod::find($data['payment_method_id']);

        $payment = $order->payment()->create([
            'method' => $payment->slug,
            'payment_method_id' => $payment->id,
            'paymob_card_id' => isset($data['card_id']) ? $data['card_id'] : null
        ]);

        return $payment;
    }


    public function executeBNPLRequest(OrderModel $order , $update = false) {
        $order = OrderModel::find($order->id);
        $customer = $order->customer;
        if($update){
            $customer->credit_wallet = $customer->credit_wallet - $order->BNPLTransaction->amount;
            $order->BNPLTransaction()->update(['status'=>'order_updated']);
        }else{
            $order->paid_type = OrderModel::PAID_TYPE_BNPL;
            $order->is_paid = OrderModel::ORDER_PAID;
            $order->save();
        }

        $customer->credit_wallet += $order->final_total;
        $customer->save();
        $customer->BNPLTransactions()->create(['order_id'=>$order->id,'amount'=>$order->final_total,'release_date'=>Carbon::now()->addDays(config('robosto.BNPL_RELEASE_AFTER'))->toDateString()]);
    }
    /**
     * Start order Processing
     * @param OrderModel $order
     * @return void
     */
    public function orderProcessing(OrderModel $order) {
        Event::dispatch('app.order.processing.start', $order);
        logOrderActionsInCache($order->id, 'before_update_area');

        // Store Estimated Preparing Time in Logs
        $preparingTime = $order->items_qty_shipped * config('robosto.QAUNTITY_PREPARING_TIME');
        $this->storeOrderEstimatedLogs($order, 'preparing_time', $preparingTime);

        Log::info("Before Dispatch Process Job");
        // Second, Check Order Items
        CheckOrderItems::dispatch($order);
        // CallSysAdmins::dispatch($order);
    }

    /**
     * Start order Processing
     * @param OrderModel $order
     * @return void
     */
    public function redispatchOrder(OrderModel $order) {
        Event::dispatch('order.actual_logs', [$order, OrderLogsActual::ORDER_REDISPATCH]);

        logOrderActionsInCache($order->id, 'redispatch-order');

        // Redisptach Te Order
        CheckOrderItems::dispatch($order);

        return true;
    }

    /**
     * Update Inventory Warehouse
     *
     * @param OrderModel $order
     * @param array $items
     * @return void
     */
    public function decreaseInventoryArea(OrderModel $order, array $items) {
        Event::dispatch('app.order.update_inventory_area', $order);

        logOrderActionsInCache($order->id, 'start_decrease_inventory_area');

        // Loop through Items and Decrease each quantity from Area
        foreach ($items as $item) {
            $productInInventoryArea = InventoryArea::where('product_id', $item['product_id'])
                    ->where('area_id', $order->area_id)
                    ->first();
            $productInInventoryArea->total_qty = $productInInventoryArea->total_qty - $item['qty'];
            $productInInventoryArea->save();
        }

        logOrderActionsInCache($order->id, 'finish_decrease_inventory_area');
    }

    /**
     * Prepare Items for Update Inventories
     *
     * @param OrderModel $order
     * @return array
     */
    public function prepareItemsForUpdateInventory(OrderModel $order) {
        $items = [];
        // Loop through Items and Make it available in specified format
        foreach ($order->items as $item) {

            if ($item->bundle_id) {

                foreach ($item->bundleItems as $bundleItem) {
                    $items[] = [
                        'product_id' => $bundleItem['product_id'],
                        'qty' => $bundleItem['quantity'] * $item->qty_shipped,
                    ];
                }
            } else {
                $items[] = [
                    'product_id' => $item->product_id,
                    'qty' => $item->qty_shipped,
                ];
            }
        }

        return $items;
    }

    /**
     * Store Order Estimated time Logs
     * @param OrderModel $order
     * @param string $logType
     * @param $logTime
     */
    public function storeOrderEstimatedLogs(OrderModel $order, string $logType, $logTime) {
        DB::table('order_logs_estimated')->insert([
            'order_id' => $order->id,
            'aggregator_id' => $order->aggregator_id,
            'log_type' => $logType,
            'log_time' => $logTime,
        ]);

        logOrderActionsInCache($order->id, 'estimated_time');
    }

    /**
     * Check Order Items Available
     * @param OrderModel $order
     * @return void
     * @throws InvalidOptionsException
     */
    public function checkOrderItems(OrderModel $order) {
        logOrderActionsInCache($order->id, 'before_check_items');
        Event::dispatch('app.order.check_items_avaialable', $order);

        logOrderActionsInCache($order->id, 'start_check_items');

        $orderItems = $order->items->toArray();

        // refactor orderitems if contains bundle
        $mergedItems = $this->getMergedItems($this->refactorOrderItemsData($order->items));
        Log::info(['mergedItems ' => $mergedItems]);

        // Check Items through Service Class
        $checkItemsAvailableInWarehouses = new CheckItemsAvailableInAreaWarehouses($mergedItems, $order->area_id);

        $allWarehousesHaveItems = $checkItemsAvailableInWarehouses->getAllWarehousesHaveItems();

        logOrderActionsInCache($order->id, 'call_check_items_class');

        // If all Items in Order are Available with it's Quantity
        if ($allWarehousesHaveItems['items_found']) {
            Log::info("Order Items Are Found");
            Event::dispatch('order.actual_logs', [$order, OrderLogsActual::ORDER_ITEMS_FOUND]);

            // then, get Warehouses which have items
            $warehousesHaveItems = $allWarehousesHaveItems['warehouses'];

            Event::dispatch('order.actual_logs', [$order, OrderLogsActual::ORDER_ITEMS_FOUND_IN_WAREHOUSES, implode('-', $warehousesHaveItems)]);

            // Cache Warehouses that have Items
            Cache::forget("order_{$order->id}_warehouses_have_items");
            Cache::add("order_{$order->id}_warehouses_have_items", $warehousesHaveItems);

            logOrderActionsInCache($order->id, 'items_found');

            // Run Job to handle Drivers
            Log::info("Fireeeeee Accept Order By Default Driver Job");
            AcceptOrderByDefaultDriver::dispatch($order, $warehousesHaveItems);
            // GetAndStoreDrivers::dispatch($order, $warehousesHaveItems);
            Log::info("try creadit card payment for => " . $order->id);
            PayViaCreditCard::dispatch($order);
        } else {
            Log::info("Order Items Are NOOOOT Found");
            Event::dispatch('order.actual_logs', [$order, OrderLogsActual::ORDER_ITEMS_NOT_FOUND]);

            // Send SMS to Galal
            $this->sendSMSToGalala('01091447677', $order);

            // Order items doesn't exist with enough quantity, then Get Highest Warehouse that have max items
            $itemsAfterUpdated = $checkItemsAvailableInWarehouses->handleWarehouseWithHighestItems($allWarehousesHaveItems['warehouses']);
            Log::info(['itemsAfterUpdated: ' => $itemsAfterUpdated]);
            $bundles = $this->checkItemsExistsInBundle($order, $itemsAfterUpdated);
            Log::info(['$bundles: ' => $bundles]);
            if (count($bundles)) {

                // get items in bundle not enough (available qty)
                // get items in bundle out of stock
                // get all availble updated items that can use for this order
                // compare bundle count in stock(total in stock= real) with availble qty in that can make the most bundle count
                $outOfStockItems = $this->buildBunldeOutOfStock($order, $itemsAfterUpdated);
                $cleanBunldeNotEnough = $this->cleanBunldeNotEnough($outOfStockItems, $order, $itemsAfterUpdated);
                // calculate bundle items
                $itemsAfterUpdated = $this->reBuildItemsAfterUpdatedIfMutltiBundle($order, $cleanBunldeNotEnough);
            }
            Log::info(['$newItemsAfterUpdated: ' => $itemsAfterUpdated]);
            // Cache Order Changes
            Cache::forget("order_{$order->id}_has_changes_in_items");
            Cache::rememberForever("order_{$order->id}_has_changes_in_items", function () use ($itemsAfterUpdated) {
                return $itemsAfterUpdated;
            });

            logOrderActionsInCache($order->id, 'items_not_all_found');

            // Update Order Status
            $this->updateOrderStatus($order, OrderModel::STATUS_WAITING_CUSTOMER_RESPONSE);

            // Send Notification to Customer with order changes
            $dataToCustomer = [
                'title' => 'Order Status', 'body' => 'Your Order Items Cannot shipped All',
                'details' => ['order_id' => $order->id, 'key' => 'order_items_not_found']
            ];
            $this->sendNotificationToCustomer($order, $dataToCustomer);

            // Fire Job to call the customer after minutes
            CallCustomerJob::dispatch($order->customer, $order, CallCustomerJob::ORDER_WAITING_TYPE)->delay(now()->addMinutes(config('robosto.CALL_CUSTOMER_WAITING_ORDER')));

            Event::dispatch('app.order.send_changes_to_customer', $order);
            Event::dispatch('order.actual_logs', [$order, OrderLogsActual::ORDER_ITEMS_CHANGED]);
            logOrderActionsInCache($order->id, 'send_changes_to_customer');
        }
    }

    public function buildBunldeOutOfStock($order, $itemsAfterUpdated) {
        $outOfStockItems = [
            'out_of_stock' => []
        ];
        // check if item in out stock is in product in bundle
        // then replace this item with the parent product bundle if found
        // we push parent porduct bundle once and make sure to not repeat
        $orderBundleItems = $order->items()->where('bundle_id', '!=', null)->get();
        $orderItems = $order->items()->get();
        $warehouseItems = $itemsAfterUpdated['items'];
        if ($warehouseItems['out_of_stock']) {

            foreach ($warehouseItems['out_of_stock'] as $outOfStockItem) {

                foreach ($orderItems as $item) {
                    $itemOutStockCollection = collect($outOfStockItems['out_of_stock']);
                    // if product out of stock item in order item
                    if ($item->product_id == $outOfStockItem['product_id']) {

                        $filtered = $itemOutStockCollection->where('product_id', $outOfStockItem['product_id']);
                        // prevent dublication of items if found
                        if (count($filtered) == 0) {
                            $outOfStockItems['out_of_stock'][] = ['product_id' => $outOfStockItem['product_id']];
                        }
                    } else {
                        // if product out of stock item NOT in order item
                        // then we check if order item is bundle
                        if ($item->bundle_id) {
                            $productInBunde = $item->bundleItems()->where('product_id', $outOfStockItem['product_id'])->first();
                            if ($productInBunde) {
                                $filtered = $itemOutStockCollection->where('product_id', $item->product_id);
                                // prevent dublication of items if found
                                if (count($filtered) == 0) {
                                    $outOfStockItems['out_of_stock'][] = ['product_id' => $item->product_id];
                                }
                            }
                        }
                    }
                }
            }
        }

        foreach ($outOfStockItems['out_of_stock'] as $k => $item) {
            $orderItem = $order->items()->where('product_id', $item['product_id'])->first();
            // remove item if not found
            if (!$orderItem) {
                unset($outOfStockItems['out_of_stock'][$k]);
            }
        }
        Log::info(['buildBunldeOutOfStock' => $outOfStockItems]);
        return $outOfStockItems;
    }

    // clean all items belongs to product of type bundle in the out of stock
    public function cleanBunldeNotEnough($outOfStockItems, $order, $itemsAfterUpdated) {
        $newItemsAfterUpdated = array();
        $allItems = [
            'not_enough' => [],
            'out_of_stock' => []
        ];
        $itemsAfter['not_enough'] = $itemsAfterUpdated['items']['not_enough'];
        Log::info([' $itemsAfter not_enough: ' => $itemsAfter['not_enough']]);
        // loop through not enough
        // compare items in not enough with items in out of stock
        //  if found then unset from item not enough
        $outOfStockItemsCollection = collect($outOfStockItems);
        foreach ($outOfStockItemsCollection->flatten() as $product) {
            //Log::info(['$outOfStock' => $product]);
            $orderBundleItem = $order->items()
                            ->where('product_id', $product)
                            ->where('bundle_id', '!=', null)->first();

            if ($orderBundleItem) {
                if (count($orderBundleItem->bundleItems) > 0) {
                    foreach ($orderBundleItem->bundleItems as $bunldeItem) {
                        foreach ($itemsAfter['not_enough'] as $k => $itemNotEnough) {
                            if ($bunldeItem['product_id'] == $itemNotEnough['product_id']) {
                                $orderItem = $order->items()->where('product_id', $itemNotEnough['product_id'])->where('bundle_id', null)->first();
                                if (!$orderItem) {
                                    unset($itemsAfter['not_enough'][$k]);
                                }
                            }
                        }
                    }
                }
            }
        }
        Log::info([' $itemsAfter not_enough: ' => $itemsAfter['not_enough']]);
        $allItems = [
            'not_enough' => $itemsAfter['not_enough'], //$itemsAfterUpdated['items']['not_enough'],
            'out_of_stock' => $outOfStockItems['out_of_stock']
        ];

        $newItemsAfterUpdated = [
            'warehouse_id' => $itemsAfterUpdated['warehouse_id'],
            'items' => $allItems
        ];
        Log::info(['cleanBunldeNotEnough' => $newItemsAfterUpdated]);
        return $newItemsAfterUpdated;
    }

    public function reBuildItemsAfterUpdatedIfMutltiBundle($order, $itemsAfterUpdated) {
        if (!$itemsAfterUpdated['items']['not_enough']) {
            return $itemsAfterUpdated;
        }

        $newItemsAfterUpdated = array();
        $allItems = [
            'not_enough' => [],
            'out_of_stock' => $itemsAfterUpdated['items']['out_of_stock']
        ];

        $bundleQtyAvialable = []; // perpare array to save all item qty available in bundle to be accepted
        $warehouseItems = $itemsAfterUpdated['items'];
        $orderItems = $order->items()->get();
        $bundleItems = $order->items()->where('bundle_id', '!=', null)->get();
        // get all items not enough
        // calculate the $maxAllowedBundle
        if ($warehouseItems['not_enough']) {
            $explodedProducts = [];
            foreach ($bundleItems as $item) {
                if (!in_array($item['product_id'], array_column($warehouseItems['out_of_stock'], 'product_id'))) {
                    foreach ($item->bundleItems as $bundleItem) {
                        if (in_array($bundleItem['product_id'], array_column($warehouseItems['not_enough'], 'product_id'))) {
                            if (isset($explodedProducts[$item->product_id])) {
                                $arrayedProducts = $explodedProducts[$item->product_id]['items'];
                                array_push($arrayedProducts, ['product_id' => $bundleItem['product_id'], 'quantity' => $bundleItem['quantity']]);
                                $new_item = ['quantity' => $item['qty_shipped'], 'items' => $arrayedProducts];
                            } else {
                                $new_item = ['quantity' => $item['qty_shipped'], 'items' => [['product_id' => $bundleItem['product_id'], 'quantity' => $bundleItem['quantity']]]];
                            }
                            $explodedProducts[$item->product_id] = $new_item;
                        }
                    }
                }
            }
            Log::info($explodedProducts);

            $this->notEnoughToOrder = $warehouseItems['not_enough'];
            $this->outOfStockToOrder = $warehouseItems['out_of_stock'];
            foreach ($explodedProducts as $key => $productedBundle) {
                $requested = $productedBundle['quantity'];
                $available = 0;

                Log::info('$requested ' . $requested . ' of ' . $key);
                for ($i = 1; $i <= $requested; $i++) {
                    $qtyExist = true;
                    foreach ($productedBundle['items'] as $item) {
                        foreach ($this->notEnoughToOrder as $notEn) {
                            if ($notEn['product_id'] == $item['product_id']) {
                                Log::info('product_id  ' . $item['product_id']);
                                Log::info('quantity  ' . $item['quantity'] . '  - not:' . $notEn['available_qty']);
                                if ($item['quantity'] > $notEn['available_qty']) {
                                    $qtyExist = false;
                                    break;
                                }
                            }
                        }
                    }
                    if ($qtyExist) {
                        $newNotEnough = [];
                        foreach ($this->notEnoughToOrder as $notEn) {
                            foreach ($productedBundle['items'] as $item) {
                                if ($notEn['product_id'] == $item['product_id']) {
                                    $notEn['available_qty'] = $notEn['available_qty'] - $item['quantity'];
                                }
                            }
                            array_push($newNotEnough, $notEn);
                        }
                        $this->notEnoughToOrder = $newNotEnough;
                        $available++;
                    }
                }
                Log::info('available ' . $available);
                //  if available = 0 then bundle is out of stock
                if ($available == 0) {
                    $this->outOfStockToOrder[] = ['product_id' => $key];
                }

                // if available <  $requested  bundle is not enf (with available value)
                if ($available > 0 && ($available < $requested)) {
                    $this->notEnoughToOrder[] = ['product_id' => $key, 'available_qty' => $available];
                }

                // if available ==   $requested do no thing
                if ($available == $requested) {

                }
            }
            Log::info('before');
            Log::info($this->notEnoughToOrder);
            Log::info($this->outOfStockToOrder);

            $itemNotEnoughCollection = collect($this->notEnoughToOrder);
            $itemOutStockCollection = collect($this->outOfStockToOrder);
            foreach ($this->notEnoughToOrder as $k => $notEnf) {
                foreach ($orderItems as $item) {

                    // check if there is items in not enf also found in the main order request
                    // then add the not enf of items
                    if (!$item['bundle_id'] && ($notEnf['product_id'] == $item['product_id'])) {

                        if ($notEnf['available_qty'] > 0) {
                            if ($notEnf['available_qty'] < $item['qty_shipped']) {
                                $filtered = $itemNotEnoughCollection->where('product_id', $item->product_id);
                                if (count($filtered) == 0) {
                                    $this->notEnoughToOrder[] = ['product_id' => $notEnf['product_id'], 'available_qty' => $notEnf['available_qty']];
                                } else {
                                    unset($this->notEnoughToOrder[$k]);
                                    $this->notEnoughToOrder[] = ['product_id' => $notEnf['product_id'], 'available_qty' => $notEnf['available_qty']];
                                }
                            } else {
                                unset($this->notEnoughToOrder[$k]);
                            }
                        }

                        // out of stock
                        if ($notEnf['available_qty'] == 0) {
                            unset($this->notEnoughToOrder[$k]);
                            $this->outOfStockToOrder[] = ['product_id' => $notEnf['product_id']];
                        }
                    }
                    // if items in new not enf  and not in oreder request then remove from items not enf
                    $itemBunlde = $order->items()
                                    ->where('bundle_id', '!=', null)
                                    ->where('product_id', $notEnf['product_id'])->first();
                    if (!$itemBunlde && ($notEnf['product_id'] != $item['product_id'])) {
                        unset($this->notEnoughToOrder[$k]);
                    }
                }
            }

            // fix array index by rearrange
            $this->notEnoughToOrder = array_values(array_filter($this->notEnoughToOrder));
            $this->outOfStockToOrder = array_values(array_filter($this->outOfStockToOrder));
            $allItems = [
                'not_enough' => $this->notEnoughToOrder,
                'out_of_stock' => $this->outOfStockToOrder
            ];
        }
        $newItemsAfterUpdated = [
            'warehouse_id' => $itemsAfterUpdated['warehouse_id'],
            'items' => $allItems
        ];
        Log::info(['before   not_enough $newItemsAfterUpdated: ' => $newItemsAfterUpdated]);

        return $newItemsAfterUpdated;
    }

    public function checkItemsExistsInBundle($order, $itemsAfterUpdated) {

        $bundles = [];
        $warehouseItems = $itemsAfterUpdated['items'];

        if ($warehouseItems['not_enough']) {
            foreach ($warehouseItems['not_enough'] as $notEnoughItem) {
                $orderBundleItems = $order->items()->where('bundle_id', '!=', null)->get();
                foreach ($orderBundleItems as $orderBundle) {
                    $productInBunde = $orderBundle->bundleItems()->where('product_id', $notEnoughItem['product_id'])->exists();
                    if ($productInBunde) {
                        $bundles[] = $orderBundle->bundle_id;
                    }
                }
            }
        }
        if ($warehouseItems['out_of_stock']) {
            foreach ($warehouseItems['out_of_stock'] as $outOfStockItem) {
                $orderBundleItems = $order->items()->where('bundle_id', '!=', null)->get();
                foreach ($orderBundleItems as $orderBundle) {
                    $productInBunde = $orderBundle->bundleItems()->where('product_id', $outOfStockItem['product_id'])->exists();
                    if ($productInBunde) {
                        $bundles[] = $orderBundle->bundle_id;
                    }
                }
            }
        }
        return array_unique($bundles);
    }

    public function refactorOrderItemsData($orderItems) {
        $allItems = [];
        $itemObj = [];
        foreach ($orderItems as $item) {
            $itemObj['order_id'] = $item->order_id;
            $itemObj['id'] = $item->product_id;
            $itemObj['qty'] = $item->qty_shipped;
            $itemObj['bundle_id'] = $item->bundle_id;
            $itemObj['order_item_id'] = $item->id;
            array_push($allItems, $itemObj);
        }
        return $allItems;
    }

    private function getMergedItems(array $items) {


        $data['bundle_items'] = [];
        $mergedItems = [];
        $mainItems = [];
        $newBundleItems = [];
        foreach ($items as $item) {

            $product = Product::find($item['id']);

            if ($product->bundle_id) {
                $bundleItems = $product->bundle->items;
                foreach ($bundleItems as $bundleItem) {
                    $data['bundle_items'][$product->id][] = ['product_id' => $bundleItem['product_id'], 'qty_shipped' => $bundleItem['quantity'] * $item['qty'], 'order_item_id' => $item['order_item_id'], 'bundle_id' => $product['bundle_id']];
                }
            } else {
                $mainItems[$product->id] = ['product_id' => $item['id'], 'qty_shipped' => $item['qty'], 'order_item_id' => $item['order_item_id'], 'bundle_id' => null];
            }
        }

        $itemBundleQty[] = 0;

        foreach ($data['bundle_items'] as $key => $items) {

            foreach ($items as $item) {
                // && !array_search($item['id'], array_keys($newBundleItems[$item['id']]))
                if (!isset($newBundleItems[$item['product_id']])) {

                    $itemBundleQty[$item['product_id']] = !isset($itemBundleQty[$item['product_id']]) ? $item['qty_shipped'] : $itemBundleQty[$item['product_id']];
                    $newBundleItems[$item['product_id']] = ['product_id' => $item['product_id'], 'qty_shipped' => $itemBundleQty[$item['product_id']], 'order_item_id' => $item['order_item_id'], 'bundle_id' => $item['bundle_id']];
                } else {
                    $itemBundleQty[$item['product_id']] = $itemBundleQty[$item['product_id']] + $item['qty_shipped'];
                    unset($newBundleItems[$item['product_id']]);
                    $newBundleItems[$item['product_id']] = ['product_id' => $item['product_id'], 'qty_shipped' => ($itemBundleQty[$item['product_id']] ), 'order_item_id' => $item['order_item_id'], 'bundle_id' => $item['bundle_id']];
                }
            }
        }
        $mergedItemQty[] = 0;

        if (count($mainItems) > 0 && count($newBundleItems) == 0) {
            $mergedItems = $mainItems;
        } elseif (count($mainItems) == 0 && count($newBundleItems) > 0) {
            $mergedItems = $newBundleItems;
        } elseif (count($mainItems) > 0 && count($newBundleItems) > 0) {
            $mergedItems = $mainItems;
            foreach ($mergedItems as $key => $item) {

                $mergedItemQty[$item['product_id']] = $item['qty_shipped'];
                foreach ($newBundleItems as $bundleItem) {

                    if (!isset($mergedItems[$bundleItem['product_id']])) {

                        $mergedItemQty[$bundleItem['product_id']] = !isset($mergedItemQty[$bundleItem['product_id']]) ? $bundleItem['qty_shipped'] : $mergedItemQty[$bundleItem['product_id']];
                        $mergedItems[$bundleItem['product_id']] = ['product_id' => $bundleItem['product_id'], 'qty_shipped' => $mergedItemQty[$bundleItem['product_id']], 'order_item_id' => $item['order_item_id'], 'bundle_id' => $bundleItem['bundle_id']];
                    } else {
                        $mergedItemQty[$bundleItem['product_id']] = $mergedItemQty[$item['product_id']] + $bundleItem['qty_shipped'];
                        unset($mergedItems[$item['product_id']]);
                        $mergedItems[$item['product_id']] = ['product_id' => $item['product_id'], 'qty_shipped' => ($mergedItemQty[$item['product_id']] ), 'order_item_id' => $item['order_item_id'], 'bundle_id' => $bundleItem['bundle_id']];
                    }
                }
            }
        }
        ksort($mergedItems);

        return $mergedItems;
    }

    /**
     * Send SMS to the phone
     */
    public function sendSMSToGalala($phone, OrderModel $order) {
        $text = config('app.env') . ": " . "في مشكلة حصلت في الاوردر ياجلال .  اوردر رقم " . $order->id . ' للعميل رقم ' . $order->customer_id;
        $lang = request()->header('lang') ?? 'ar';
        $sender = 'Robosto';

        $sms = new SendSMS($phone, $text, $lang, $sender);

        return $sms->send();
    }

    /**
     * @param OrderModel $order
     * @param $status
     * @return void
     */
    public function updateOrderStatus(OrderModel $order, $status) {
        Event::dispatch('app.order.update_status', $order);

        $order->status = $status;
        $order->save();
    }

    /**
     * Show the specified order.
     *
     * @param array $data
     * @return bool
     */
    public function customerOrderChangesResponse(OrderModel $order, array $data) {
        Event::dispatch('app.order.customer_reponse_changes_reponse', $order);

        if ($data['action'] == 'cancel') {
            Event::dispatch('order.actual_logs', [$order, OrderLogsActual::ORDER_CUSTOMER_CANCELLED]);
            // then, Cancel Order
            $this->updateOrderStatus($order, OrderModel::STATUS_CANCELLED_FOR_ITEMS);

            logOrderActionsInCache($order->id, 'customer_rejected_changes');

            // Run Cancellation Process to Update Inventory
            CustomerCancelledOrder::dispatch($order);

            return true;
        }

        Event::dispatch('order.actual_logs', [$order, OrderLogsActual::ORDER_CUSTOMER_ACCEPTED]);

        logOrderActionsInCache($order->id, 'customer_accept_changes');

        // if Customer Accept Changes
        CustomerAcceptedOrderChanges::dispatch($order);

        return true;
    }

    /**
     * Show the specified order.
     *
     * @param OrderModel $order
     * @return bool
     */
    public function customerAcceptedChanges(OrderModel $order) {
        Event::dispatch('app.order.customer_accept_changes', $order);
        // Get Changes from Cache
        $changesInItems = Cache::get("order_{$order->id}_has_changes_in_items");

        // Adjustment Inventory Area again
        $this->adjustmentItemsInInventoryArea($order, array_merge($changesInItems['items']['not_enough'], $changesInItems['items']['out_of_stock']));

        // Update Order Item with New Changed
        $this->updateOrderItemsWithChanges($order, $changesInItems['items']);

        // Check Order Promotion if Applied
        //$this->checkConfirmedOrderPromotion($order);
        // Handle Customer Wallet if Order Changed
        $this->handleCustomerWalletAfterConfirmChanges($order);

        // Second, Check Order Items Again
        CheckOrderItems::dispatch($order);

        // Run Job to handle Drivers
        // GetAndStoreDrivers::dispatch($order, [$changesInItems['warehouse_id']]);
        return true;
    }

    /**
     * Update Inventory Warehouse
     *
     * @param OrderModel $order
     * @param array $newItems
     * @return void
     */
    public function adjustmentItemsInInventoryArea(OrderModel $order, array $newItems) {
        Event::dispatch('app.order.update_inventory_area', $newItems);

        logOrderActionsInCache($order->id, 'start_adjustment_inventory_area');

        // Loop through Items and Decrease each quantity from Area
        foreach ($newItems as $item) {
            $productIsBundle = Product::where('id', $item['product_id'])->where('bundle_id', '!=', null)->first();
            if ($productIsBundle) {
                foreach ($productIsBundle->bundleItems as $bunldeItem) {
                    $productInInventoryArea = InventoryArea::where('product_id', $bunldeItem['product_id'])
                            ->where('area_id', $order->area_id)
                            ->first();
                    $oldItem = $order->items()->where('product_id', $item['product_id'])->first();
                    $qtyToUpdate = isset($item['available_qty']) ? ($oldItem->qty_ordered - $item['available_qty']) * $bunldeItem['quantity'] : $oldItem->qty_ordered * $bunldeItem['quantity'];
                    $productInInventoryArea->total_qty = $productInInventoryArea->total_qty + $qtyToUpdate;
                    $productInInventoryArea->save();
                }
                // Update Bundle Products stock In Inventory Area And Inventory Warehouse
                // $this->updateBundleProductsStockInAreaAndWarehouse($order);
            } else {
                $productInInventoryArea = InventoryArea::where('product_id', $item['product_id'])
                        ->where('area_id', $order->area_id)
                        ->first();
                $oldItem = $order->items()->where('product_id', $item['product_id'])->first();
                $qtyToUpdate = isset($item['available_qty']) ? ($oldItem->qty_ordered - $item['available_qty']) : $oldItem->qty_ordered;
                $productInInventoryArea->total_qty = $productInInventoryArea->total_qty + $qtyToUpdate;
                $productInInventoryArea->save();
            }
        }
        logOrderActionsInCache($order->id, 'finish_adjustment_inventory_area');
    }

    /**
     * @param OrderModel $order
     * @param $newItems
     * @return void
     */
    public function updateOrderItemsWithChanges(OrderModel $order, $newItems) {
        Event::dispatch('app.order.update_items_changes', $order);

        logOrderActionsInCache($order->id, 'update_order_items');

        $oldItems = $order->items;

        // Update Not enough items with exist qty
        if (isset($newItems['not_enough'])) {
            foreach ($newItems['not_enough'] as $item) {
                $orderItem = $oldItems->where('product_id', $item['product_id'])->first();
                $orderItem->qty_shipped = $item['available_qty'];
                $orderItem->total = $item['available_qty'] * $orderItem->price;
                $orderItem->save();
            }
        }
        // Update out of stock item from Order items
        if (isset($newItems['out_of_stock'])) {
            foreach ($newItems['out_of_stock'] as $item) {
                $orderItem = $oldItems->where('product_id', $item['product_id'])->first();
                $orderItem->qty_shipped = 0;
                $orderItem->total = 0;
                $orderItem->save();
            }
        }

        $itemsTotal = $order->items->sum('total');
        $deliverFees = $this->handleDeliveryFees($itemsTotal);
        $baseFinalTotal = $itemsTotal + $deliverFees + $order->customer_balance + config('robosto.DEFAULT_TAX');

        // Update Order Price and total qty
        $order->delivery_chargs = $deliverFees;
        $order->items_shipped_count = count($order->items->where('qty_shipped', '>', 0));
        $order->items_qty_shipped = $order->items->sum('qty_shipped');
        $order->sub_total = $itemsTotal;
        $order->final_total = $baseFinalTotal;
        $order->save();
    }

    /**
     * @param OrderModel $order
     *
     * @return mixed
     */
    public function checkConfirmedOrderPromotion(OrderModel $order) {
        if ($order->coupon_code) {
            // if that the coupon applied is free_shipping coupon
            if ($order->coupon_code == config('robosto.FREE_SHIPPING_COUPON')) {
                return $this->handleFreeShippingCouponAfterChanges($order);
            }


            $promotion = Promotion::where('promo_code', $order->coupon_code)->first();
            $apply = false;
            // Determine if this order has PromoCode or Has FirstOrder Discount
            if ($promotion) {
                // Check that this Promotion has MinimumOrderAmount
                if (is_null($promotion->minimum_order_amount)) {
                    $apply = true;
                } else {
                    // If Has, Check that the order valid for condition
                    if ($order->final_total > $promotion->minimum_order_amount) {
                        $apply = true;
                    }
                }

                // Check that this Promotion has MinimumOrderQuantitiy
                if (is_null($promotion->minimum_items_quantity)) {
                    $apply = true;
                } else {
                    // If Has, Check that the order valid for condition
                    if ($order->items_qty_shipped > $promotion->minimum_items_quantity) {
                        $apply = true;
                    }
                }
            } else {
                $apply = true;
            }

            if ($apply) {
                // Apply Discount on the New Order Total
                $order->final_total -= (($order->discount / 100) * $order->final_total);
                $order->save();
            }
        }
    }

    /**
     * @param OrderModel $order
     *
     * @return bool
     */
    public function handleFreeShippingCouponAfterChanges(OrderModel $order) {
        // Apply Discount on the New Order TotalApply Free shipping coupon
        $order->final_total -= $order->delivery_chargs;
        $order->delivery_chargs = 0;
        $order->save();

        return true;
    }

    /**
     * @param OrderModel $order
     *
     * @return mixed
     */
    public function handleCustomerWalletAfterConfirmChanges(OrderModel $order) {
        if (abs($order->customer_balance) > $order->sub_total) {
            $this->addMoneyToCustomerWallet($order, abs($order->customer_balance) - $order->sub_total);
        }
    }

    /**
     * @param OrderModel $order
     * @param array $warehouses
     *
     * @return bool
     */
    public function acceptOrderByDefaultDriver(OrderModel $order, array $warehouses) {
        $warehouse = $warehouses[0];
        // Get Default Driver From Warehouse
        $driver = Driver::where('warehouse_id', $warehouse)->where('default_driver', Driver::DEFAULT_DRIVER)->first();
        if (!$driver) {
            $driver = Driver::where('warehouse_id', $warehouse)->first();
        }

        // Accept Order Automatically Be Default Driver
        $this->driverAcceptedNewOrder($order, $driver, false);

        return true;
    }

    public function acceptShippingOrderForDriverAndCollector(OrderModel $order, bool $bickup) {
        $warehouse = $order->warehouse_id;
        // Get Default Driver From Warehouse
        $driver = Driver::where('warehouse_id', $warehouse)->where('default_driver', Driver::DEFAULT_DRIVER)->first();
        if (!$driver) {
            $driver = Driver::where('warehouse_id', $warehouse)->first();
        }

        // Accept Order Automatically Be Default Driver
        $this->driverAndCollectorAcceptedShippingOrder($order, $driver, $bickup);

        return true;
    }

    /**
     * insert idle drivers sorted by rank(based on google map time)
     *
     * @param array $warehouses
     * @param OrderModel $order
     * @return bool|mixed|void
     */
    public function orderDriverDispatching(array $warehouses, OrderModel $order) {
        // first of all, check that the order still in pending status
        if ($order->status != OrderModel::STATUS_PENDING) {
            return false;
        }

        Event::dispatch('app.order.drivers_dispatching', $order);

        logOrderActionsInCache($order->id, 'start_driver_dispatching');

        // Get All Drivers
        $drivers = $this->getIdleDrivers($order, $warehouses);

        logOrderActionsInCache($order->id, 'got_idle_drivers');

        Event::dispatch('app.order.get_avaialable_drivers', $order);

        // If No Driver Available, Call Job again every 10 sec.
        if ($drivers->isEmpty()) {

            logOrderActionsInCache($order->id, 'no_idle_drivers_found');

            GetAndStoreDrivers::dispatch($order, $warehouses)->delay(now()->addSeconds(10));
            return true;
        }

        // Sort Drivers by nearest driver to customer
        logOrderActionsInCache($order->id, 'start_got_drivers_from_google');

        $sortedDrivers = $this->getSortedDriversByShortestTime($drivers, $order);

        // If Google Distance Can't Find Route for this driver
        if (!count($sortedDrivers)) {
            logOrderActionsInCache($order->id, 'cannot_got_drivers_from_google');

            GetAndStoreDrivers::dispatch($order, $warehouses)->delay(now()->addSeconds(10));
            return true;
        }

        logOrderActionsInCache($order->id, 'got_drivers_from_google');

        logOrderActionsInCache($order->id, 'insert_idle_drivers_in_db');

        // Save Drivers sorted in DB
        $this->insertOrderDriverDispatching($sortedDrivers, $order);

        // Cache Drivers
        Cache::forget("order_{$order->id}_drivers");
        Cache::forget("order_{$order->id}_driver_notified");
        Cache::rememberForever("order_{$order->id}_drivers", function () use ($order) {
            return OrderDriverDispatch::where('order_id', $order->id)->get();
        });

        // Run Job to Send Driver Notification
        SendOrderToDriver::dispatch($order);
        return true;
    }

    /**
     * Get All Drivers
     * @param $order
     * @param array $warehouses
     * @return mixed
     */
    public function getIdleDrivers(OrderModel $order, array $warehouses) {
        // if the order has assigned_driver_id
        if ($order->assigned_driver_id) {
            Log::info("Order Has Assigned Driver");
            return Driver::where('id', $order->assigned_driver_id)->get();
        }

        // if the order has shadow area
        if ($order->shadowArea) {
            Log::info("Order Has Shadow Area");
            return $this->getShadowDriver($order);
        }

        // Get all idle drivers for selected warehouses(stores)
        return Driver::whereIn('warehouse_id', $warehouses)
                        ->where('is_online', 1)
                        ->where('can_receive_orders', Driver::CAN_RECEIVE_ORDERS)
                        ->where(function ($query) {
                            $query->whereIn('availability', [Driver::AVAILABILITY_IDLE, Driver::AVAILABILITY_BACK, Driver::AVAILABILITY_ONLINE])
                            ->orWhere([
                                ['availability', '=', Driver::AVAILABILITY_DELIVERY],
                                ['multi_order', '=', Driver::HAS_MULTI_ORDER]
                            ]);
                        })
                        ->get();
    }

    /**
     * @param OrderModel $order
     *
     * @return mixed
     */
    private function getShadowDriver(OrderModel $order) {
        return Driver::where('area_id', $order->area_id)
                        ->where('is_online', 1)
                        ->where('has_shadow_area', Driver::HAS_SHADOW_AREA)
                        ->where('can_receive_orders', Driver::CAN_RECEIVE_ORDERS)
                        ->where(function ($query) {
                            $query->whereIn('availability', [Driver::AVAILABILITY_IDLE, Driver::AVAILABILITY_BACK, Driver::AVAILABILITY_ONLINE])
                            ->orWhere([
                                ['availability', '=', Driver::AVAILABILITY_DELIVERY],
                                ['multi_order', '=', Driver::HAS_MULTI_ORDER]
                            ]);
                        })
                        ->get();
    }

    /**
     * this function based on calculation of google api
     *
     * @param $drivers
     * @param $order
     * @return array
     */
    public function getSortedDriversByShortestTime($drivers, OrderModel $order) {
        logOrderActionsInCache($order->id, 'collect_idle_drivers');
        Event::dispatch('app.order.get_drivers_sorted', $order);
        $locations = [];
        // Get Customer Address Location
        $customerAddress = $order->address;

        logOrderActionsInCache($order->id, "before_loop");
        Log::info("idle drivers :");
        Log::info($drivers);
        // Loop through all Drivers
        foreach ($drivers as $driver) {

            // in case, the driver able to take multi order but he is on_the_way with order
            if ($driver->on_the_way) {
                continue;
            }

            logOrderActionsInCache($order->id, "prepare_driver_{$driver->id}_data");

            // Get Driver Warehouse
            $driverWarehouse = $driver->warehouse;

            // Get Driver Location from Cache (Redis)
            $driverData = Cache::get('driver_' . $driver->id);

            if ($driverData == null) {
                $driverData['lat'] = $driverWarehouse->latitude;
                $driverData['long'] = $driverWarehouse->longitude;
            }

            if (empty($driverData['lat']) || empty($driverData['long'])) {
                $driverData['lat'] = $driverWarehouse->latitude;
                $driverData['long'] = $driverWarehouse->longitude;
            }

            logOrderActionsInCache($order->id, "driver_lat_in_cache_is_{$driverData['lat']}");
            logOrderActionsInCache($order->id, "driver_long_in_cache_is_{$driverData['long']}");

            // Prepare Driver Data
            $locationObg = [
                'driver_id' => $driver->id,
                'warehouse_id' => $driver->warehouse_id,
                // Origins: Warehouse Lat/Long
                'origins' => [
                    ['lat' => $driverWarehouse->latitude, 'long' => $driverWarehouse->longitude]
                ],
                // Destinations: Driver Lat/Long  and  Customer Lat/Long
                'dsetinations' => [
                    [
                        'lat' => $driverData['lat'],
                        'long' => $driverData['long']
                    ],
                    [
                        'lat' => $customerAddress->latitude,
                        'long' => $customerAddress->longitude
                    ],
                ],
            ];
            logOrderActionsInCache($order->id, "driver_{$driver->id}_data_ready");
            // Add The Driver Data to locations
            array_push($locations, $locationObg);
        }

        logOrderActionsInCache($order->id, 'start_call_google');

        // Finally, Calculate Distance among Drivers and Customer Address
        $distanceService = new DistanceService();
        return $distanceService->getNearesDriversToCustomer($locations);
    }

    /**
     * insert drivers sorted by rank(based on google map time)
     * @param $drivers
     * @param OrderModel $order
     * @return void
     */
    public function insertOrderDriverDispatching($drivers, OrderModel $order) {
        Event::dispatch('app.order.insert_avaialable_drivers_in_db', $order);
        $rank = 1;
        $data = [];
        $trial = Cache::get("order_{$order->id}_dispatch_trial", 1);
        foreach ($drivers as $driver) {
            $data[] = [
                'driver_id' => $driver['driver_id'],
                'warehouse_id' => $driver['warehouse_id'],
                'order_id' => $order->id,
                'rank' => $rank,
                'delivery_time' => $driver['time'] * 60,
                'trial' => $trial,
                'created_at' => now()->format('Y:m:d H:i:s'),
                'updated_at' => now()->format('Y:m:d H:i:s'),
            ];
            $rank++;
        }

        logOrderActionsInCache($order->id, 'start_insert_idle_drivers_in_db');

        // Update Cache with Next Trial
        Cache::put("order_{$order->id}_dispatch_trial", $trial + 1);

        // Insert to DB
        DB::table('order_driver_dispatches')->insert($data);

        logOrderActionsInCache($order->id, 'insert_idle_drivers_in_db_done');
    }

    /**
     * Send Notification to Driver
     * @param OrderModel $order
     * @return bool
     * @throws InvalidOptionsException
     */
    public function dispatchReadyDriver(OrderModel $order) {
        logOrderActionsInCache($order->id, 'start_dispatch_drivers');

        Event::dispatch('app.order.ready_drivers_dispatching', $order);

        // first of all, check that the order still in pending status
        if ($order->status != OrderModel::STATUS_PENDING) {
            return false;
        }

        // Check if the Order has assigned_driver_id
        if ($order->assigned_driver_id) {
            $assignedDriver = Driver::find($order->assigned_driver_id);
            Log::info("Order Was Assigned To The Driver -> " . $assignedDriver->name);
            // Driver Accepted New Order
            $this->driverAcceptedNewOrder($order, $assignedDriver);
            return true;
        }

        // Get Last Driver Notified
        $currentNotifiedDriver = Cache::get("order_{$order->id}_driver_notified");

        $readyDriver = $this->getCurrentNotifidDriver($order, $currentNotifiedDriver);

        // if no Ready Driver, Then Insert Drivers again and send again
        if (!$readyDriver) {

            logOrderActionsInCache($order->id, 'cannot_get_ready_driver');

            $warehousesHaveItemsFromCache = Cache::get("order_{$order->id}_warehouses_have_items");

            GetAndStoreDrivers::dispatch($order, $warehousesHaveItemsFromCache);

            return true;
        }

        // Update Driver Notified Data
        $this->updateCurrentDriverNotified($readyDriver);

        // Send Notification to the driver
        $response = $this->connectWithCurrentDriver($order, $readyDriver);

        // if this driver not available now
        if ($response == false) {
            // Store Current Driver Notified in Cache
            $this->cacheCurrentDriverNotified($order, $readyDriver);

            // Call Job to Send Notification to the Next Driver after 5 sec.
            SendOrderToDriver::dispatch($order)->delay(now()->addSeconds(5));

            return true;
        }

        // Trigger Realtime FCM DB
        $this->triggerFCMDB($readyDriver, $currentNotifiedDriver);

        // Store Current Driver Notified in Cache
        $this->cacheCurrentDriverNotified($order, $readyDriver);

        // Call Job to Send Notification to the Next Driver after 10 sec.
        SendOrderToDriver::dispatch($order)->delay(now()->addSeconds(10));

        return true;
    }

    /**
     * @param OrderModel $order
     * @param mixed $currentNotifiedDriver
     *
     * @return mixed
     */
    private function getCurrentNotifidDriver(OrderModel $order, $currentNotifiedDriver) {
        // Get Drivers Dispatched
        $getAllDrivers = OrderDriverDispatch::where('order_id', $order->id)->get();

        // if no Driver Notified, then the first time
        if (!$currentNotifiedDriver) {
            logOrderActionsInCache($order->id, 'no_current_notified');
            // Get Ready Driver
            $readyDriver = $getAllDrivers->where('rank', 1)->whereNull('dispatched_at')->first();
        } else {

            logOrderActionsInCache($order->id, 'there_is_current_notified');
            // Get Last Driver Has Notification
            $lastDriver = $getAllDrivers->where('id', $currentNotifiedDriver->id)->first();

            if ($lastDriver->status == OrderDriverDispatch::STATUS_PENDING) {

                logOrderActionsInCache($order->id, "last_driver_is_{$lastDriver->driver_id}");

                $addSecondsToLastDriverNotification = Carbon::createFromTimestamp($lastDriver->dispatched_at)->addSeconds(10)->timestamp;
                // Check the last driver after 15 sec, if after 15 sec. then send to the next
                if (now()->timestamp >= $addSecondsToLastDriverNotification) {

                    logOrderActionsInCache($order->id, "cancel_last_driver_{$lastDriver->driver_id}");

                    OrderDriverDispatch::find($lastDriver->id)->update([
                        'status' => OrderDriverDispatch::STATUS_CANCELLED
                    ]);
                }
            }

            logOrderActionsInCache($order->id, 'get_ready_driver');
            // Get Ready Driver
            $readyDriver = $getAllDrivers->where('rank', $lastDriver->rank + 1)->whereNull('dispatched_at')->first();
        }

        return $readyDriver;
    }

    /**
     * @param mixed $readyDriver
     *
     * @return mixed
     */
    private function updateCurrentDriverNotified(OrderDriverDispatch $readyDriver) {
        // Update Driver Notified Data
        $readyDriver = OrderDriverDispatch::find($readyDriver->id);
        $readyDriver->status = OrderDriverDispatch::STATUS_PENDING;
        $readyDriver->dispatched_at = now()->timestamp;
        $readyDriver->save();
    }

    /**
     * @param mixed $readyDriver
     *
     * @return bool
     */
    private function connectWithCurrentDriver(OrderModel $order, OrderDriverDispatch $readyDriver) {
        $driver = $readyDriver->driver;
        $notvalidStatus = [Driver::AVAILABILITY_OFFLINE, Driver::AVAILABILITY_BREAK, Driver::AVAILABILITY_TRANSACTION, Driver::AVAILABILITY_EMERGENCY];
        if (in_array($driver->availability, $notvalidStatus)) {
            return false;
        }

        logOrderActionsInCache($order->id, "send_notification_to_driver_{$readyDriver->driver_id}");

        Log::info("Send Order " . $order->id . " To Driver " . $readyDriver->driver_id);

        // Send Notification to the driver
        $dataToDriver = ['title' => 'New Order', 'body' => 'New order For you', 'data' => ['order_id' => $order->id, 'key' => 'order_new']];
        $this->sendNotificationToDriver($readyDriver->driver_id, $order, $dataToDriver);

        $this->sendCallToDriver($readyDriver->driver_id, $order);

        return true;
    }

    /**
     * @param OrderModel $order
     * @param OrderDriverDispatch $readyDriver
     *
     * @return void
     */
    private function cacheCurrentDriverNotified(OrderModel $order, OrderDriverDispatch $readyDriver) {
        Cache::forget("order_{$order->id}_driver_notified");
        Cache::rememberForever("order_{$order->id}_driver_notified", function () use ($readyDriver) {
            return $readyDriver;
        });

        Cache::forget("order_{$order->id}_delivery_time_in_seconds");
        Cache::add("order_{$order->id}_delivery_time_in_seconds", $readyDriver->delivery_time);
    }

    /**
     * @param OrderDriverDispatch $driver
     * @param OrderDriverDispatch $currentNotifiedDriver
     *
     * @return bool
     */
    private function triggerFCMDB(OrderDriverDispatch $driver, ?OrderDriverDispatch $currentNotifiedDriver) {
        $database = app('firebase.database');

        // Remove Last Driver from RTDB
        if ($currentNotifiedDriver) {
            $current = $database->getReference('driver_' . $currentNotifiedDriver->driver_id);
            if ($current) {
                $current->remove();
            }
        }

        // Save Driver in Realtime DB
        $reference = $database->getReference('driver_' . $driver->driver_id);
        if ($reference) {
            $reference->set(true);
        }
        return true;
    }

    /**
     * @param $driverId
     * @param OrderModel $order
     * @param array $data
     * @return void
     * @throws InvalidOptionsException
     */
    public function sendCallToDriver($driverId, OrderModel $order) {
        logOrderActionsInCache($order->id, 'start_send_call_to_driver');
        Event::dispatch('app.order.send_call_to_driver', $order);

        $driver = Driver::findOrFail($driverId);

        Log::info("Call The Driver with Phone " . $driver->phone_work);

        if ($driver && $driver->phone_work) {

            $url = 'https://api-gateway.innocalls.com/api/order-confirmation';

            $headers = [
                'Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzUxMiJ9.eyJpc3MiOiJodHRwczpcL1wvZ2xvY29tc3lzdGVtcy5jb20iLCJhdWQiOiJodHRwczpcL1wvZ2xvY29tc3lzdGVtcy5jb20iLCJpYXQiOjEzNTY5OTk1MjQsIm5iZiI6MTM1Njk5OTUyNCwiaWQiOiI0YjhmNGNmYi1jMTgwLTQ5NGYtODVmOS1iNjYwY2M2YzA0OWQiLCJnY2kiOiJhYjQ3YWYwYi1mMzcyLTQxOTItYjc4OC04OWVhYTJjYzQ0ZTMifQ.U4t2CkzUFTqNZ1v0bZ9I-AVq88QSvUGz5J_MtdJeFfTFNITCBJYsuvbdjNHZP3pEpwG-DwX7qwzbfGOCmp77Zw',
                'Cookie: __cfduid=daad03eb7971ddd1911b7555cbe085dc81617009241; DO-LB=node-187928191|YGmNG|YGmMH',
                'Content-Type: application/json'
            ];

            $data = [
                'phone' => '2' . $driver->phone_work,
                'call_flow_id' => '5f0f0fd97af09f0023836b3c',
                'order_number' => $order->id,
                'type' => 'order_number.order_cost',
                'order_cost' => 0,
                'order_currency' => "SAR",
            ];

            $response = requestWithCurl($url, 'POST', $data, $headers);
        }
    }

    /**
     * Show the specified order.
     *
     * @param OrderModel $order
     * @param Driver $driver
     * @param array $data
     * @return bool|mixed
     */
    public function driverNewOrderResponse(OrderModel $order, Driver $driver, array $data) {
        // first of all, check that the order still in pending
        if ($order->status != OrderModel::STATUS_PENDING) {
            return false;
        }

        logOrderActionsInCache($order->id, "driver_{$driver->id}_new_order_reponse");
        Event::dispatch('app.order.new_order_driver_reponse', $order);

        if ($data['action'] == 'cancel') {
            logOrderActionsInCache($order->id, "driver_{$driver->id}_new_order_rejected");
            // Call Job
            DriverRejectedNewOrder::dispatch($order, $driver, $data['reason'] ?? null);

            // send notification to area manager
            $payload['model'] = $order;
            Event::dispatch('admin.alert.driver_cancelled_order', [$driver, $payload]);

            return $order;
        }

        Event::dispatch('order.actual_logs', [$order, OrderLogsActual::ORDER_DRIVER_ACCEPTED]);
        logOrderActionsInCache($order->id, "driver_{$driver->id}_new_order_accept");

        // Update Order Status To Prepairing
        $order->status = OrderModel::STATUS_PREPARING;
        $order->save();

        // If Driver Accepted New Order
        DriverAcceptedNewOrder::dispatch($order, $driver);

        // Remove Driver From RTDB
        $database = app('firebase.database');
        $database->getReference('driver_' . $driver->id)->remove();

        return $order;
    }

    /**
     * Driver Rejected New Order.
     *
     * @param OrderModel $order
     * @param Driver $driver
     * @param string $reason
     * @return bool
     */
    public function driverRejectedNewOrder(OrderModel $order, Driver $driver, string $reason) {
        logOrderActionsInCache($order->id, "start_Job_for_driver_{$driver->id}_new_order_rejected");
        Event::dispatch('app.order.new_order_driver_rejected', $order);

        // Update Driver Data
        $driver = OrderDriverDispatch::where('order_id', $order->id)->where('driver_id', $driver->id)->get()->last();
        $driver->status = OrderDriverDispatch::STATUS_CANCELLED;
        $driver->reason = $reason;
        $driver->save();

        // Call Job To Send Notification to the Next
        SendOrderToDriver::dispatch($order);

        return true;
    }

    /**
     * Driver Accepted New Order.
     *
     * @param OrderModel $order
     * @param Driver $driver
     * @return bool|mixed
     * @throws InvalidOptionsException
     */
    public function driverAcceptedNewOrder(OrderModel $order, Driver $driver, bool $driverAccepted = true) {
        if($order->status!=OrderModel::STATUS_PENDING){
            Log::info("trying this job again for order " . $order->id);
            return true;
        }
        Log::info("Driver " . $driver->id . ' Accepted New Order ' . $order->id);
        logOrderActionsInCache($order->id, "start_Job_for_driver_{$driver->id}_new_order_accepted");
        Event::dispatch('app.order.new_order_driver_accepted', $order);

        // Get Collector
        $collector = $this->getReadyCollector($driver->warehouse_id);

        // Update Order Data
        logOrderActionsInCache($order->id, "update_order_to_preparing");
        $order->status = OrderModel::STATUS_PREPARING;
        $order->driver_id = $driver->id;
        $order->warehouse_id = $driver->warehouse_id;
        $order->collector_id = $collector->id;
        $order->save();

        // Return Order Flagged to False
        logOrderActionsInCache($order->id, "reset_order_to_unflagged");
        Cache::forget("order_{$order->id}_flagged");

        // Update Driver Data
        logOrderActionsInCache($order->id, "update_driver_{$driver->id}_to_delivery");
        $driver->availability = Driver::AVAILABILITY_DELIVERY;
        $driver->save();

        // Decrease Products From Inventory Warehouse
        $this->decreaseInventoryWarehouse($order, $this->prepareItemsForUpdateInventory($order));

        // Decrease Products SKU From Inventory Products
        $this->decreaseInventoryProduct($order);

        // Update Bundle Products stock In Inventory Area And Inventory Warehouse
        $this->updateBundleProductsStockInAreaAndWarehouse($order);

        logOrderActionsInCache($order->id, "start_handle_customer_wallet");
        // Subtract Order Price from Customer Wallet
        $this->handleCustomerWallet($order);
        logOrderActionsInCache($order->id, "finish_handle_customer_wallet");

        // Send Notification to Customer
        $dataToCustomer = ['title' => 'Order Prepairing', 'body' => 'Your Order is Prepairing', 'details' => ['key' => 'order_preparing']];
        $this->sendNotificationToCustomer($order, $dataToCustomer);

        // Send Notification to Collector
        $dataToCollector = ['title' => 'طلب جديد', 'body' => 'لديك طلب جديد من فضلك قم بتجهيزه', 'details' => ['pending_orders' => $this->getPendingOrdersForWarehouse($order->warehouse_id), 'key' => 'new_order']];
        $this->sendNotificationToCollector($order, $dataToCollector);
        logOrderActionsInCache($order->id, 'notification_to_collector_was_sent');

        if ($driverAccepted) {
            // Store Estimated Delivery Time in Logs
            $deliveryTime = OrderDriverDispatch::where('order_id', $order->id)->where('driver_id', $driver->id)->whereNotNull('dispatched_at')->orderBy('id', 'desc')->first();

            if ($deliveryTime) {
                $deliveryTime = $deliveryTime->delivery_time;

                logOrderActionsInCache($order->id, "order_delivery_time_from_driver_{$driver->id}_is_{$deliveryTime}");

                logOrderActionsInCache($order->id, "start_save_delivery_time");
                $this->storeOrderEstimatedLogs($order, 'delivery_time', $deliveryTime);
            }
        }

        Event::dispatch('order.actual_logs', [$order, OrderLogsActual::ORDER_ITEMS_PREPARING]);
        Event::dispatch('app.order.status_changed', $order);

        return true;
    }



    public function driverAndCollectorAcceptedShippingOrder(OrderModel $order, Driver $driver, bool $pickup = false) {
        Log::info("Driver " . $driver->id . ' Accepted New Order ' . $order->id);
        Event::dispatch('app.order.new_order_driver_accepted', $order);
        // Get Collector
        $collector = $this->getReadyCollector($driver->warehouse_id);
        $order->status = OrderModel::STATUS_PREPARING;
        $order->driver_id = $driver->id;
        $order->warehouse_id = $driver->warehouse_id;
        $order->collector_id = $collector->id;
        $order->save();
        $driver->availability = Driver::AVAILABILITY_DELIVERY;
        $driver->save();
        Event::dispatch('order.actual_logs', [$order, OrderLogsActual::ORDER_ITEMS_PREPARING]);
        Event::dispatch('app.order.status_changed', $order);
        if($pickup){
            $this->collectorOrderReadyToPickup($order);
        }
    }

    /**
     * Update Inventory Warehouse
     *
     * @param OrderModel $order
     * @param array $items
     * @return void
     */
    public function decreaseInventoryWarehouse(OrderModel $order, array $items) {
        Event::dispatch('app.order.update_inventory_warehouse', $order);

        logOrderActionsInCache($order->id, 'start_decrease_inventory_warehouse');

        foreach ($items as $item) {

            logProductStockInCache($order->id, $item['product_id'], 'decrease_inventory_warehouse', $item['qty']);

            $productInInventoryWarehouse = InventoryWarehouse::where('product_id', $item['product_id'])->where('warehouse_id', $order->warehouse_id)->where('area_id', $order->area_id)->first();
            $productInInventoryWarehouse->qty = $productInInventoryWarehouse->qty - $item['qty'];
            $productInInventoryWarehouse->save();
        }

        logOrderActionsInCache($order->id, 'finsh_decrease_inventory_warehouse');
    }

    /**
     * Update Inventory Product
     *
     * @param OrderModel $order
     * @return void
     */
    public function decreaseInventoryProduct(OrderModel $order) {

        Event::dispatch('app.order.update_inventory_products', $order);

        logOrderActionsInCache($order->id, 'start_decrease_inventory_products');

        //getMergedItems
        $orderItems = $this->getMergedItems($this->refactorOrderItemsData($order->items()->get()));
        $orderItems = array_values($orderItems); // reindex
        // foreach ($order->items as $item) {
        foreach ($orderItems as $item) {
            // Get Product SKU ordered BY Expiration Date
            $skusInInventoryProduct = InventoryProduct::where('product_id', $item['product_id'])
                    ->where('warehouse_id', $order->warehouse_id)
                    ->orderBy('exp_date')
                    ->get();

            $myItem = $order->items()->where('id', $item['order_item_id'])->first();

            $qtyMustDecreased = $item['qty_shipped'];
            foreach ($skusInInventoryProduct as $sku) {
                // qty in inventory
                $qtyBeforeDecreased = $sku->qty;
                if ($sku->qty >= $qtyMustDecreased) {
                    // Decrease in DB
                    $sku->qty = $qtyBeforeDecreased - $qtyMustDecreased; // 11 - 8 = 3
                    $sku->save();
                    // Save SKU in Order Items
                    //    $myItem->skus()->create([
                    //        'product_id' => $item['product_id'],
                    //        'order_id' => $order->id,
                    //        'sku' => $sku->sku,
                    //        'qty' => $qtyMustDecreased
                    //    ]);
                    OrderItemSku::create([
                        'product_id' => $item['product_id'],
                        'order_id' => $order->id,
                        'order_item_id' => $item['order_item_id'],
                        'sku' => $sku->sku,
                        'qty' => $qtyMustDecreased
                    ]);

                    //    $item->skus()->create([
                    //        'product_id' =>  $item['product_id'],
                    //        'order_id' => $order->id,
                    //        'sku' => $sku->sku,
                    //        'qty' => $qtyMustDecreased
                    //    ]);
                    break;
                }

                // if that the base QTY from inventory is ZERO, then don't create item-sku
                if ($sku->qty == 0) {
                    continue;
                }

                // Decrease in DB
                $sku->qty = 0;
                $sku->save();

                //     Save SKU in Order Items
                //    $item->skus()->create([
                //        'product_id' =>  $item['product_id'],
                //        'order_id' => $order->id,
                //        'sku' => $sku->sku,
                //        'qty' => $qtyBeforeDecreased
                //    ]);
                //    $myItem->skus()->create([
                //        'product_id' => $item['product_id'],
                //        'order_id' => $order->id,
                //        'sku' => $sku->sku,
                //        'qty' => $qtyBeforeDecreased
                //    ]);

                OrderItemSku::create([
                    'product_id' => $item['product_id'],
                    'order_id' => $order->id,
                    'order_item_id' => $item['order_item_id'],
                    'sku' => $sku->sku,
                    'qty' => $qtyBeforeDecreased
                ]);
                // Update New Qty must decreased
                $qtyMustDecreased -= $qtyBeforeDecreased;
            }
        }
        logOrderActionsInCache($order->id, 'finish_decrease_inventory_products');
    }

    public function updateBundleProductsStockInAreaAndWarehouse($order) {

        Event::dispatch('app.order.update_bundle_qty_in_area_and_warehouse', $order);
        logOrderActionsInCache($order->id, 'update_bundle_qty_in_area_and_warehouse');
        $bundleItems = $order->items()->where('bundle_id', '!=', null)->get();

        if ($order->oldItems()->count() > 0) {
            $bundleItemsOld = $order->oldItems()->where('bundle_id', '!=', null)->get();
            $bundleItems = $bundleItems->merge($bundleItemsOld);
            $bundleItems = $bundleItems->unique('product_id');
        }


        Log::info('update_bundle_products_stock_in_area_and_warehouse count: ' . $bundleItems->count());
        foreach ($bundleItems as $product) {
            $productBundleItems = $product->bundleItems;

            // check qty stock in stock for product that is bundle
            $qtyInStock = [];
            foreach ($productBundleItems as $item) { // item in product bundle
                $invAreay = InventoryArea::where(['product_id' => $item['product_id'], 'area_id' => $order->area_id])->first();
                if ($invAreay) {
                    $invQty = $invAreay->total_qty;
                    $bundleQty = $item->quantity;
                    $qty = $invQty > 0 ? $invQty / $bundleQty : 0; // 15 / 4 = 3.75 = 3

                    array_push($qtyInStock, intval($qty));
                } else {
                    array_push($qtyInStock, 0);
                }
            }

            $totalInStock = min($qtyInStock);

            if ($totalInStock < 1) {
                // Set total_qty = 0 in area
                $productInInventoryArea = InventoryArea::where('product_id', $product['product_id'])->where('area_id', $order->area_id)->first();
                $productInInventoryArea->total_qty = 0;
                $productInInventoryArea->save();
                // Set qty = 0 in warehouse
                $productInInventoryWarehouse = InventoryWarehouse::where('product_id', $product['product_id'])->where('warehouse_id', $order->warehouse_id)->where('area_id', $order->area_id)->first();
                $productInInventoryWarehouse->qty = 0;
                $productInInventoryWarehouse->save();
            } else {
                // Set total_qty = 1 in area
                $productInInventoryArea = InventoryArea::where('product_id', $product['product_id'])->where('area_id', $order->area_id)->first();
                $productInInventoryArea->total_qty = 1;
                $productInInventoryArea->save();
                // Set qty = 1 in warehouse
                $productInInventoryWarehouse = InventoryWarehouse::where('product_id', $product['product_id'])->where('warehouse_id', $order->warehouse_id)->where('area_id', $order->area_id)->first();
                $productInInventoryWarehouse->qty = 1;
                $productInInventoryWarehouse->save();
            }
        }
    }

    /**
     * Increase Inventory Product
     *
     * @param OrderModel $order
     * @return void
     */
    public function increaseInventoryProduct(OrderModel $order) {
        Event::dispatch('app.order.increase_inventory_products', $order);

        logOrderActionsInCache($order->id, 'start_increase_inventory_products');

        foreach ($order->skus()->get() as $item) {
            // Get Product SKU
            $skusInInventoryProduct = InventoryProduct::where('product_id', $item->product_id)
                    ->where('sku', $item->sku)
                    ->where('warehouse_id', $order->warehouse_id)
                    ->first();

            if ($skusInInventoryProduct) {
                $skusInInventoryProduct->qty += $item->qty;
                $skusInInventoryProduct->save();
            }
        }

        logOrderActionsInCache($order->id, 'finish_increase_inventory_products');
        return true;
    }

    /**
     * @param OrderModel $order
     */
    public function handleCustomerWallet(OrderModel $order) {
        $getCustomerWalletSettingsFromCache = Cache::get("customer_{$order->customer_id}_wallet_settings");

        // check if the customer allow to pay via Wallet or Not
        if ($getCustomerWalletSettingsFromCache) {
            $this->subtractMoneyFromCustomerWallet($order, $order->final_total);
        }
    }

    /**
     * @param OrderModel $order
     * @param $amount
     */
    public function subtractMoneyFromCustomerWallet(OrderModel $order, $amount) {
        $order->customer->subtractMoney($amount, $order->id, $order->increment_id);
    }

    /**
     * @param int $warehouseId
     * @return mixed
     */
    public function getPendingOrdersForWarehouse(int $warehouseId) {
        return $this->model->where('status', OrderModel::STATUS_PREPARING)->where('warehouse_id', $warehouseId)->count();
    }

    /**
     * Show the specified order.
     *
     * @param OrderModel $order
     * @return bool|mixed
     * @throws InvalidOptionsException
     */
    public function collectorOrderReadyToPickup(OrderModel $order) {
        logOrderActionsInCache($order->id, "update_order_to_ready_to_pickup");

        // Get Driver
        $driver = $order->driver;
        $readyToPickupOrder = $driver->activeOrders->where('status', OrderModel::STATUS_READY_TO_PICKUP)->first();

        // Update Order Status
        $order->status = OrderModel::STATUS_READY_TO_PICKUP;
        $order->save();

        // Fire New Order Dispatch JOB
        RoboDistanceJob::dispatch($order);

        Event::dispatch('app.order.driver_ready_to_pickup', $order);
        Event::dispatch('app.order.status_changed', $order);
        Event::dispatch('order.actual_logs', [$order, OrderLogsActual::ORDER_ITEMS_PREPARED]);

        return $order;

        // if the driver has at least one ready_to_pickup order, then no notification will be sent
        // if (!$readyToPickupOrder) {
        //     $dataToDriver = ['title' => 'Order Ready to pickup', 'body' => 'Order ready to pickup', 'data' => ['order_id' => $order->id, 'key' => 'ready_to_pickup']];
        //     $this->sendNotificationToDriver($order->driver_id, $order, $dataToDriver);
        // }
        $dataToDriver = ['title' => 'Order Ready to pickup', 'body' => 'Order ready to pickup', 'data' => ['order_id' => $order->id, 'key' => 'ready_to_pickup']];
        $this->sendNotificationToDriver($order->driver_id, $order, $dataToDriver);

        return $order;
    }

    /**
     * Show the specified order.
     *
     * @param array $data
     * @return bool|mixed
     * @throws InvalidOptionsException
     */
    public function driverConfirmReceivingItems(array $data) {
        $order = OrderModel::find($data['order_id']);
        $driver = Driver::find($data['driver_id']);

        logOrderActionsInCache($order->id, "driver_{$driver->id}_confirm_receiving_items");

        // Update Order Status
        logOrderActionsInCache($order->id, "update_order_to_on_the-way");
        $order->status = OrderModel::STATUS_ON_THE_WAY;
        $order->save();

        // Update Driver Status
        // $driver->can_receive_orders = Driver::CANNOT_RECEIVE_ORDERS;
        // $driver->save();
        // Send Notification to Customer
        $dataToCustomer = ['title' => 'Order Status', 'body' => 'Your Order is On the Way', 'details' => ['key' => 'order_on_the_way']];
        $this->sendNotificationToCustomer($order, $dataToCustomer);

        Event::dispatch('app.order.driver_confirm_receiving_items', $order);
        Event::dispatch('order.actual_logs', [$order, OrderLogsActual::ORDER_DRIVER_ITEMS_CONFIRMED]);
        Event::dispatch('app.order.status_changed', $order);
        if($order->shippment){
            if($order->customer){
                Event::dispatch('shippment.log',[$order->shippment,ShippmentLogs::SHIPPMENT_TRIAL_ON_THE_WAY]);
            }else{
                Event::dispatch('shippment.log',[$order->shippment,ShippmentLogs::SHIPPMENT_PICK_UP_ORDER_ON_THE_WAY]);
            }
        }
        return $order;
    }

    /**
     * Show the specified order.
     *
     * @param array $data
     * @return bool|mixed
     * @throws InvalidOptionsException
     */
    public function driverConfirmReceivingReturnItemsFromCustomer(array $data) {
        $order = OrderModel::find($data['order_id']);
        $driver = $data['driver'];

        logOrderActionsInCache($order->id, "driver_{$driver->id}_confirm_receiving_return_items");
        Event::dispatch('app.order.driver_confirm_receiving_return_items', $order);

        // update order and order items with refunded items qty and refunded money
        $orderItems = $order->items;
        $totalQtyRefunded = 0;
        $totalAmoutRefunded = 0;
        foreach ($orderItems as $item) {
            if ($item->qty_refunded > 0) {
                $amountRefunded = $item->qty_refunded * $item->price;
                $item->amount_refunded = $amountRefunded;
                $item->base_amount_refunded = $amountRefunded;
                $totalQtyRefunded = $totalQtyRefunded + $item->qty_refunded;
                $totalAmoutRefunded = $totalAmoutRefunded + $amountRefunded;
                $item->save();
            }
        }

        $order->items_qty_refunded = $totalQtyRefunded;
        $order->final_total = $order->final_total - $totalAmoutRefunded;
        $order->sub_refunded = $totalAmoutRefunded;
        $order->final_refunded = $totalAmoutRefunded;

        $order->save();

        // update wallet
        $this->addMoneyToCustomerWallet($order, $totalAmoutRefunded);

        // update order status to return if all items returns
        if ($totalQtyRefunded == $order->items_shipped_qty_ordered) {
            $order->status = OrderModel::STATUS_RETURNED;
            $order->save();
        }

        logOrderActionsInCache($order->id, "update_return_order_to_on_the-way");

        Event::dispatch('order.actual_logs', [$order, OrderLogsActual::ORDER_DRIVER_RETURN_ITEMS_CONFIRMED]);

        return $order;
    }

    /**
     * @param OrderModel $order
     * @param $amount
     */
    public function addMoneyToCustomerWallet(OrderModel $order, $amount) {
        $order->customer->addMoney($amount, $order->id, $order->increment_id);
    }

    /**
     * Show the specified order.
     *
     * @param array $data
     * @return bool|mixed
     * @throws InvalidOptionsException
     */
    public function driverOrderAtPlace(array $data) {
        $order = OrderModel::find($data['order_id']);

        logOrderActionsInCache($order->id, "driver_{$data['driver_id']}_confirm_at_place");

        // Update Order Status
        logOrderActionsInCache($order->id, "update_order_to_at_place");
        $order->status = OrderModel::STATUS_AT_PLACE;
        $order->save();

        // Send Notification to Customer
        $dataToCustomer = ['title' => 'Order Status', 'body' => 'Your Order At Your Place', 'details' => ['key' => 'order_at_place']];
        $this->sendNotificationToCustomer($order, $dataToCustomer);

        Event::dispatch('app.order.driver_at_place', $order);
        Event::dispatch('order.actual_logs', [$order, OrderLogsActual::ORDER_DRIVER_ITEEMS_AT_PLACE]);
        Event::dispatch('app.order.status_changed', $order);

        return $order;
    }

    /**
     * Driver Delivered the Order to the Customer
     *
     * @param OrderModel $order
     * @return bool|mixed
     */
    public function driverOrderDelivered(OrderModel $order) {
        logOrderActionsInCache($order->id, "driver_{$order->driver_id}_confirm_delivered");

        logOrderActionsInCache($order->id, "update_order_to_delivered");

        // Update Order Status
        $order->status = OrderModel::STATUS_DELIVERED;
        if ($order->is_paid == OrderModel::ORDER_NOT_PAID) {
            $order->paid_type = OrderModel::PAID_TYPE_COD;
            $order->is_paid = OrderModel::ORDER_PAID;
        }
        $order->save();

        // if the driver has many active orders, then doesnt change his status
        $driver = $order->driver;

        // Get Active orders [ On_the_Way, At_place ]
        Log::info(["Is The Driver - {$driver->id} - has [ On_the_Way, At_place ] Order ?? " => $driver->on_the_way ? "YES" : "NO"]);
        if ($driver->on_the_way == false) {
            Log::info('OrderRepository -> line:2031 driverOrderDelivered Function -> ' . $driver->id);
            $driver->availability = Driver::AVAILABILITY_BACK;
            $driver->can_receive_orders = Driver::CAN_RECEIVE_ORDERS;
            $driver->save();
            // AssignNewOrdersToDriver::dispatch($driver);
        }

        // Assign New Order to driver in case he has no active orders [ Ready_to_pickup, On_the_Way, At_place ]
        Log::info(["Driver - {$driver->id} - Active Orders" => count($driver->activeOrders)]);
        if (!count($driver->activeOrders)) {
            Log::info('From Order Delivered Function ==> Assign New Orders On The Driver -> ' . $driver->id);
            AssignNewOrdersToDriver::dispatch($driver);
        }
        if(!$order->shippment_id){
            // Update Customer Delivered Orders
            $customer = $order->customer;
            $customer->delivered_orders += 1;
            $customer->save();
            // Order Delivered Jobs
            SetCustomerTag::dispatch($customer);
            HandleCustomerInvitationInOrder::dispatch($order);
        }
        // Delete The Order from Cache current Order
        $activeOrders = Cache::get("current_active_orders");
        if ($activeOrders && is_array($activeOrders) && in_array($order->id, array_values($activeOrders))) {
            unset($activeOrders[$order->driver_id]);
            Cache::put("current_active_orders", $activeOrders);
        }

        if ($order->promotion) {
            HandleOneOrderTags::dispatch($order);
        }
        Log::alert("............. Start Fire Events ............");
        Event::dispatch('driver.order-delivered', $order->id);
        if($order->driver_id){
            Event::dispatch('driver.order-delivered-bonus', $order->driver_id);
        }
        Event::dispatch('app.order.delivered', $order);
        Event::dispatch('order.actual_logs', [$order, OrderLogsActual::ORDER_DRIVER_ITEMS_DELIVERED]);
        Event::dispatch('app.order.status_changed', $order);

        return true;
    }

    /**
     * Show the specified order.
     *
     * @param OrderModel $order
     * @param $collected
     * @return bool|mixed
     */
    public function checkCollectedAmount(OrderModel $order, $collected) {
        logOrderActionsInCache($order->id, "start_handle_amount_collected");
        $baseFinalTotal = $order->final_total;
        if ($order->is_paid == OrderModel::ORDER_PAID && $order->paid_type == OrderModel::PAID_TYPE_CC) {
            $order->final_total = 0;
        }

        if ($order->is_paid == OrderModel::ORDER_PAID && $order->paid_type == OrderModel::PAID_TYPE_BNPL) {
            $order->final_total = 0;
        }

        // if Customer Pay More that order price
        if ($collected > $order->final_total) {
            $status = $this->collectedAmountMoreThanOrderPrice($order, $collected);
        } else {
            $status = $this->collectedAmountLessThanOrEqualOrderPrice($order, $collected);
        }

        $order->final_total = $baseFinalTotal;

        return $status;
    }

    /**
     * Customer Pay More than Order Price.
     *
     * @param OrderModel $order
     * @param $collected
     * @return bool|mixed
     */
    public function collectedAmountMoreThanOrderPrice(OrderModel $order, $collected) {
        // First Add remaining amount to Customer Wallet
        $remainingAmount = $collected - $order->final_total;

        $this->addMoneyToCustomerWallet($order, $remainingAmount);

        // Add collected amount to Driver Wallet
        $this->addMoneyToDriverWallet($order, $collected);

        return ['status' => 'done'];
    }

    /**
     * @param OrderModel $order
     * @param $amount
     */
    public function addMoneyToDriverWallet(OrderModel $order, $amount) {
        $order->driver->addMoney($amount, $order->id, $order->increment_id);
    }

    /**
     * Customer Pay Less than or Equal Order Price.
     *
     * @param OrderModel $order
     * @param $collected
     * @return bool|mixed
     */
    public function collectedAmountLessThanOrEqualOrderPrice(OrderModel $order, $collected) {
        $subtractFromCustomer = false;

        // if Customer Pay Less that or Equal Order price
        if ($collected < $order->final_total) {
            // Now, Collected Amount less than Order price, Check Buffer
            $orderPriceBuffer = config('robosto.ORDER_PRICE_BUFFER');
            if ($collected + $orderPriceBuffer < $order->final_total) {
                return ['status' => 'not_allowed'];
            }
            // Now We can accept amount from the customer, and subtract remaining from the customer
            $subtractFromCustomer = true;
        }

        // Customer Pay Exact order price
        $this->addMoneyToDriverWallet($order, $collected);

        // Subtract Remaining from the Customer if Pay less than order price and buffer allow that
        if ($subtractFromCustomer) {
            $this->subtractMoneyFromCustomerWallet($order, $order->final_total - $collected);
        }

        return ['status' => 'done'];
    }

    /**
     * Show the specified order.
     *
     * @param array $data
     * @return bool|mixed
     */
    public function customeRratingOrder(OrderModel $order, array $data) {
        logOrderActionsInCache($order->id, "customer_rating_order");

        // Update Order Status
        $order->comment()->create([
            'rating' => $data['rating'],
            'comment' => $data['comment'],
        ]);

        // current rate == 5 then check if latest 2 orders for customer has been reated with 5 stars to send sms
        $this->sendSMSForLatest3Orders($order,$data);

        Event::dispatch('app.order.rating', $order);

        Event::dispatch('order.actual_logs', [$order, OrderLogsActual::ORDER_CUSTOMER_RATING]);

        return $order;
    }

    private function sendSMSForLatest3Orders(OrderModel $order, $data) {
        if (isset($data['rating']) && $data['rating'] == 5) {
            $latestRatedOrders = OrderModel::where(['customer_id' => $order->customer_id, 'status' => OrderModel::STATUS_DELIVERED])->latest()->take(3)->get();
            $orderRatingCount = 0;

            foreach ($latestRatedOrders as $row) {

                if (isset($row->comment->rating) && $row->comment->rating == 5) {
                    Log::info('order: ' . $row->comment->order_id . '  rating: ' . $row->comment->rating);
                    $orderRatingCount += 1;
                }
            }

            $smsSettingText= 'three_orders_five_stars';
            // create customer sms settings if not exists
            $CustomerSmsSetting = CustomerSmsSetting::where(['customer_id' => $order->customer_id, 'sms_type' => $smsSettingText]);
            if (!$CustomerSmsSetting->exists()) {
                CustomerSmsSetting::create(['customer_id' => $order->customer_id, 'sms_type' => $smsSettingText, 'sent' => 0]);
            }

            // check if email sent to customer
            $smsSettingNotSent = CustomerSmsSetting::where(['customer_id' => $order->customer_id, 'sms_type' => $smsSettingText , 'sent' => 0]);
            // latest 3 orders has been rated with 5 stars
            if ($orderRatingCount == 3 && $smsSettingNotSent->exists()) {
                // send SMS
                $text = "لأنك عميل مميز , تقييمك يهمنا , قيم تجربتك دلوقتي مع تطبيق روبوستو ! robosto.com/marketing";
                $this->sendSMS($order->customer->phone, $text);

                // update customer sms to true
                $smsSettingNotSent->update(['sent' => 1]);

            }
        }
    }

    /**
     * Show the specified order.
     *
     * @param array $data
     * @return bool|mixed
     */
    public function customerCancelOrder(OrderModel $order) {
        logOrderActionsInCache($order->id, "customer_cancel_order");

        // Update Order Status to Cancelled
        $this->updateOrderStatus($order, OrderModel::STATUS_CANCELLED);

        Event::dispatch('order.actual_logs', [$order, OrderLogsActual::ORDER_CANCELLED]);
        Event::dispatch('app.order.status_changed', $order);
        if($order->shippment_id){
            ShippmentOrderRouter::dispatch($order);
        }else{
         // Dispatch Cancel Order Job
         CustomerCancelledOrder::dispatch($order);
        }

        return $order;
    }

    /**
     * @param $order
     * @return bool
     * @throws InvalidOptionsException
     */
    public function customerCancelledOrderProcessing($order) {

        DB::beginTransaction();
        try {
            Log::alert("Start Cancel Order Transaction");
            // (Area stock increase)return item to area stock
            $this->increaseInventoryArea($order, $this->prepareItemsForUpdateInventory($order));

            // Return Wallet to customer if handled
            if ($order->customer_balance != 0) {
                $this->subtractMoneyFromCustomerWallet($order, $order->customer_balance);
            }

            // If Order in Prepairing Status
            if ($order->driver_id) {

                // (Warehouse stock increase)return item to warehouse stock
                $this->increaseInventoryWarehouse($order, $this->prepareItemsForUpdateInventory($order));

                // Return SKUs to Inventory Products
                $this->increaseInventoryProduct($order);

                // Update Bundle Products stock In Inventory Area And Inventory Warehouse
                $this->updateBundleProductsStockInAreaAndWarehouse($order);

                $driver = $order->driver;
                $this->handleDriverStatusWhenOrderCancelled($driver);

                $dataToDriver = ['title' => 'Customer Cancelled Order', 'body' => 'Order Cancelled', 'data' => ['order_id' => $order->id, 'key' => 'order_cancelled']];
                // send Notification to Driver
                $this->sendNotificationToDriver($order->driver_id, $order, $dataToDriver);

                // send Notification to Collector
                $dataToCollector = ['title' => 'Customer Cancelled Order', 'body' => 'Order Cancelled', 'details' => ['order_id' => $order->id, 'key' => 'order_cancelled']];
                $this->sendNotificationToCollector($order, $dataToCollector);
            }

            // Handle if this order belongs to Promotion
            if ($order->promotion) {
                $this->handlePromotionWhenOrderCancelled($order);
            }


            // Send Notifcation to the Customer
            $customer = $order->customer()->first();
            $name = explode(' ', $customer->name)[0];
            $msg = "Hello {$name}, your order #{$order->increment_id} has been canceled. We hope you will shop with us again.. right?";
            $dataToCustomer = ['title' => 'Order Status', 'body' => $msg, 'details' => ['order_id' => $order->id, 'key' => 'order_cancelled']];
            $this->sendNotificationToCustomer($order, $dataToCustomer);

            //handle bnpl orders
            if($order->paid_type == OrderModel::PAID_TYPE_BNPL){
                $order->BNPLTransaction()->update(['status'=>'canceled']);
                $order->customer()->update(['credit_wallet'=>$customer->credit_wallet - $order->final_total]);
            }

            // refund customer amount if payment method CC
            if ($order->paid_type == OrderModel::PAID_TYPE_CC && $order->is_paid == OrderModel::ORDER_PAID) {
                $this->refundToCustomer($order);
            }

            Log::alert("Commit Cancel Order Transaction");

            // Commit Changes
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $msg = "OID: " . $order->id . " => " . $e->getMessage();
            sendSMSToDevTeam($msg);

            throw $e;
        }

        return true;
    }

    /**
     * @param OrderModel $order
     *
     * @return void
     */
    private function handlePromotionWhenOrderCancelled(OrderModel $order) {
        // Return Redeems to the customer
        $this->customerRepository->updateCustomerPromotionRedeemsIfOrderCancelled($order->customer, $order->promotion_id);

        // Decrease Usage Promotion
        $this->promotionRepository->decreaseUsedPromotion($order->promotion);

        // Remove Deviceid for promotion promotionDevice
        $this->promotionRepository->removePromotionDeviceid($order);
    }

    // refund amount to customer
    private function refundToCustomer($order) {

        logOrderActionsInCache($order->id, 'refund_to_customer_wallet');
        // amount to add to customer wallet
        $amount = $order->final_total;
        $this->addMoneyToCustomerWallet($order, $amount);
        $customer = $order->customer;

        logOrderActionsInCache($order->id, 'send_cancel_sms_to_customer');
        // send sms to customer
        $text = __('sales::app.refund_to_customer', ['order' => $order->id, 'amount' => $amount]);
        return $this->sendSMS($customer->phone, $text);
    }

    /**
     * @param Driver $driver
     *
     * @return void
     */
    public function handleDriverStatusWhenOrderCancelled(Driver $driver) {
        // If the driver is offline, then do nothing
        if ($driver->is_online == 0) {
            return true;
        }

        $activeOrders = $driver->activeOrders;
        if (!count($activeOrders)) {
            Log::info('OrderRepository -> line:2280 handleDriverStatusWhenOrderCancelled Function -> ' . $driver->id);
            // Update Driver Status to Online
            $driver->availability = Driver::AVAILABILITY_IDLE;
            $driver->can_receive_orders = Driver::CAN_RECEIVE_ORDERS;
            $driver->save();
        } else {
            // if the driver has an active orders but not one of them is on_the_way
            if ($activeOrders->whereIn('status', [OrderModel::STATUS_ON_THE_WAY, OrderModel::STATUS_AT_PLACE])->count() == 0) {
                Log::info('OrderRepository -> line:2288 handleDriverStatusWhenOrderCancelled Function -> ' . $driver->id);
                $driver->can_receive_orders = Driver::CAN_RECEIVE_ORDERS;
                $driver->save();
            }
        }
    }

    /**
     * Update Inventory Warehouse
     *
     * @param OrderModel $order
     * @param array $items
     * @return void
     */
    public function increaseInventoryArea(OrderModel $order, array $items) {
        Event::dispatch('app.order.update_inventory_area', $order);

        logOrderActionsInCache($order->id, 'start_increase_inventory_area');

        // Loop through Items and Decrease each quantity from Area
        foreach ($items as $item) {
            logProductStockInCache($order->id, $item['product_id'], 'increase_inventory_area', $item['qty']);

            $productInInventoryArea = InventoryArea::where('product_id', $item['product_id'])
                    ->where('area_id', $order->area_id)
                    ->first();
            $productInInventoryArea->total_qty = $productInInventoryArea->total_qty + $item['qty'];
            $productInInventoryArea->save();
        }

        logOrderActionsInCache($order->id, 'finish_increase_inventory_area');
    }

    /**
     * Update Inventory Warehouse
     *
     * @param OrderModel $order
     * @param array $items
     * @return void
     */
    public function increaseInventoryWarehouse(OrderModel $order, array $items) {
        Event::dispatch('app.order.update_inventory_warehouse', $order);

        logOrderActionsInCache($order->id, 'start_increase_inventory_warehouse');

        foreach ($items as $item) {
            logProductStockInCache($order->id, $item['product_id'], 'increase_inventory_warehouse', $item['qty']);

            $productInInventoryWarehouse = InventoryWarehouse::where('product_id', $item['product_id'])
                    ->where('warehouse_id', $order->warehouse_id)
                    ->where('area_id', $order->area_id)
                    ->first();
            $productInInventoryWarehouse->qty = $productInInventoryWarehouse->qty + $item['qty'];
            $productInInventoryWarehouse->save();
        }

        logOrderActionsInCache($order->id, 'finish_increase_inventory_warehouse');
    }

    /**
     * @param $order
     * @return bool
     */
    public function returnOrderToWarehouseProcessing(OrderModel $order) {
        Event::dispatch('order.actual_logs', [$order, OrderLogsActual::ORDER_RETURNED_TO_WAREHOUSE]);

        // Return Items to Inventory Area
        $this->increaseInventoryArea($order, $this->prepareItemsForUpdateInventory($order));
        logOrderActionsInCache($order->id, "order_returned_to_area");

        // Return Items to Inventory Warehouse
        $this->increaseInventoryWarehouse($order, $this->prepareItemsForUpdateInventory($order));
        logOrderActionsInCache($order->id, "order_returned_to_warehouse");

        // Return SKUs to Inventory Products
        $this->increaseInventoryProduct($order);

        return true;
    }

    /**
     * @param OrderModel $order
     * @param array $items
     * @return bool
     */
    public function itemsReturnedToWarehouseProcessing(OrderModel $order, array $items) {
        Event::dispatch('order.actual_logs', [$order, OrderLogsActual::ORDER_RETURNED_TO_WAREHOUSE]);

        // Return Items to Inventory Area
        $this->increaseInventoryArea($order, $this->prepareItemsForUpdateInventory($order));
        logOrderActionsInCache($order->id, "items_returned_to_area");

        // Return Items to Inventory Warehouse
        $this->increaseInventoryWarehouse($order, $this->prepareItemsForUpdateInventory($order));
        logOrderActionsInCache($order->id, "items_returned_to_warehouse");

        // Return SKUs to Inventory Products
        $this->increaseInventoryProduct($order);

        return true;
    }

    public function itemsReturnedToWarehouseLaterProcessing(OrderModel $order, $inventoryAdjustment, array $items) {

        if ($inventoryAdjustment->status == 2) { // pending
            Event::dispatch('order.actual_logs', [$order, OrderLogsActual::ORDER_RETURNED_TO_WAREHOUSE]);

            // Return Items to Inventory Area
            $this->increaseInventoryArea($order, $this->prepareItemsForUpdateInventory($order));
            logOrderActionsInCache($order->id, "items_returned_to_area");

            // Return Items to Inventory Warehouse
            $this->increaseInventoryWarehouse($order, $this->prepareItemsForUpdateInventory($order));
            logOrderActionsInCache($order->id, "items_returned_to_warehouse");

            // Return SKUs to Inventory Products
            $this->increaseInventoryProduct($order);
        }
        return true;
    }

    /**
     * once customer
     * @param OrderModel $order
     * @param array $data
     * @return OrderModel
     * @throws InvalidOptionsException
     */
    public function calculateOrderAfterDriverUpdated(OrderModel $order, array $data) {
        $orderItems = $order->items;
        // Update Order Items with returned items
        foreach ($data as $item) {
            $itemUpdated = $orderItems->where('order_id', $order->id)->where('product_id', $item['id'])->first();
            $itemUpdated->qty_canceled = $item['qty'];
            $itemUpdated->total = ($itemUpdated->qty_shipped - $item['qty']) * $itemUpdated->price;
            $itemUpdated->save();
        }

        // Update Order Price and total qty
        $order->items_qty_cancelled = $orderItems->sum('qty_canceled');
        $order->final_total = $orderItems->sum('total');
        $order->save();

        // Send Notification to Customer with New Price
        $detailsForCustomer = [
            'title' => 'Order Edited',
            'body' => 'Order Updated',
            'details' => [
                'key' => 'order_edited',
                'total' => $order->final_total
            ]
        ];
        $this->sendNotificationToCustomer($order, $detailsForCustomer);

        return $order;
    }

    /**
     * @param OrderModel $order
     */
    public function forgetOrderCaching(OrderModel $order) {
        // Forget Cached Data for the Order
        Cache::forget("order_{$order->id}_driver_notified");
        Cache::forget("order_{$order->id}_drivers");
        Cache::forget("order_{$order->id}_dispatch_trial");
    }

    /**
     * Store Order Actual Logs
     * @param OrderModel $order
     * @param string $logType
     * @param mixed $logTime
     * @param null $notes
     *
     * @return mixed
     */
    public function storeOrderActualLogs(OrderModel $order, string $logType, $logTime, $notes = null) {
        DB::table('order_logs_actual')->insert([
            'order_id' => $order->id,
            'aggregator_id' => $order->aggregator_id,
            'log_type' => $logType,
            'log_time' => $logTime,
            'notes' => $notes,
        ]);
    }

    /**
     * @param OrderModel $order
     * @param float $amount
     */
    public function subtractMoneyFromDriverWallet(OrderModel $order, float $amount) {
        $order->driver->subtractMoney($amount);
    }

}
