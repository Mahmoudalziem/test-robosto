<?php

namespace Webkul\Customer\Http\Controllers;

use Webkul\Product\Http\Resources\ProductAll;

use Illuminate\Http\Request;
use Webkul\User\Models\Role;
use Webkul\Sales\Models\Order;
use Illuminate\Http\JsonResponse;
use Webkul\Product\Models\Product;
use Webkul\Sales\Models\OrderItem;
use Illuminate\Support\Facades\Log;

use App\Jobs\CustomerCancelledOrder;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Webkul\Promotion\Models\Promotion;
use Webkul\Sales\Models\OrderLogsActual;
use App\Jobs\CustomerAcceptedOrderChanges;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Customer\Repositories\CustomerRepository;
use Webkul\Core\Http\Controllers\BackendBaseController;
use Webkul\Customer\Http\Requests\CustomerUpdateRequest;
use Webkul\Customer\Http\Requests\CustomerRateOrderRequest;
use Webkul\Customer\Http\Resources\Customer\CustomerSingle;
use Webkul\Customer\Http\Requests\CustomerCancelOrderRequest;
use Webkul\Customer\Http\Requests\CustomerFavoriteRequest;
use Webkul\Customer\Http\Requests\CustomerOrderItemsNotFoundRequest;
use Webkul\Customer\Models\CustomerFavoriteProducts;
use Webkul\Sales\Http\Resources\OrderDetailsSingle;

class CustomerController extends BackendBaseController
{
    /**
     * CustomerRepository object
     *
     * @var CustomerRepository
     */
    protected $customerRepository;

    /**
     * OrderRepository object
     *
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * ProductRepository object
     *
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * Create a new controller instance.
     *
     * @param CustomerRepository $customerRepository
     * @param OrderRepository $orderRepository
     * @param ProductRepository $productRepository
     */
    public function __construct(CustomerRepository $customerRepository, OrderRepository $orderRepository, ProductRepository $productRepository)
    {
        $this->customerRepository = $customerRepository;
        $this->orderRepository = $orderRepository;
        $this->productRepository = $productRepository;
    }

    public function update(CustomerUpdateRequest $request)
    {
        $data = $request->only(['name', 'email', 'avatar_id']);
        Log::info('Data');
        Log::info($data);
        $customer = auth('customer')->user();
        // Create Customer
        $customer = $this->customerRepository->update($data, $customer);
        // Fire customer update Event
        Event::dispatch('customer.update', $customer);

        Log::info('Response');
        Log::info($customer);

        return $this->responseSuccess('Customer successfully updated!');
    }


    /**
     * @return JsonResponse
     */
    public function profile()
    {
        $customer = auth('customer')->user();
        return $this->responseSuccess(new CustomerSingle($customer));
    }

    /**
     * @return JsonResponse
     */
    public function favoriteProducts(Request $request)
    {
        $customer = auth('customer')->user();
        $data = new ProductAll($customer->favoriteProducts);
        return $this->responsePaginatedSuccess($data, null, $request);
    }
    /**
     * @return JsonResponse
     */
    public function updateCustomerFavoriteProductStatus(CustomerFavoriteRequest $request)
    {
        $customer = auth('customer')->user();
        $custoemrFavoriteProduct = CustomerFavoriteProducts::firstOrNew(['customer_id'=>$customer->id,"product_id"=>$request->product_id]);
        $custoemrFavoriteProduct->favorite = $request->favorite;
        $custoemrFavoriteProduct->save();
        return $this->responseSuccess('Favorite Product successfully updated!');
    }
    /**
     * @return JsonResponse
     */
    public function deleteAccount()
    {
        $customer = auth('customer')->user();
        $this->customerRepository->deleteAccount($customer);
        return $this->responseSuccess('Account deleted successfully');
    }


    /**
     * @return JsonResponse
     */
    public function getCustomerWallet()
    {
        $customer = auth('customer')->user();
        $credit =  $customer->calculatedCreditWallet();
        return $this->responseSuccess(['wallet' =>  $customer->wallet , 'credit'=>$credit]);
    }

    /**
     * Show the specified order.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function customeRratingOrder(CustomerRateOrderRequest $request)
    {
        $data = $request->only(['order_id', 'rating', 'comment']);
        $customerID = auth('customer')->id();

        // Get Order
        $order = $this->orderRepository->findOrFail($data['order_id']);
        if ($order->status != Order::STATUS_DELIVERED || $order->customer_id != $customerID || $order->comment) {
            return $this->responseError();
        }

        // Call the function that Rate the Order
        $order = $this->orderRepository->customeRratingOrder($order, $data);

        Event::dispatch('driver.customer-rating-bonus', $order->driver_id);

        return $this->responseSuccess();
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function getOrderChanges(Request $request)
    {
        $this->validate($request, [
            'order_id'      =>  'required|numeric',
        ]);

        $data = $request->only(['order_id']);
        $order = $this->orderRepository->findOrFail($data['order_id']);

        if ($order->status != Order::STATUS_WAITING_CUSTOMER_RESPONSE) {
            return $this->responseError();
        }

        // Get Changes from Cache
        $changesInItems = Cache::get("order_{$order->id}_has_changes_in_items");

        // Handle Items
        $items = $this->prepareItemsForResponse($order, $changesInItems['items']);

        return $this->responseSuccess($items, null);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function orderChangesResponse(CustomerOrderItemsNotFoundRequest $request)
    {
        $data = $request->only(['order_id', 'action']);
        $customerID = auth('customer')->id();

        // Validate Order
        $order = $this->orderRepository->findOrFail($data['order_id']);
        if ($order->status != Order::STATUS_WAITING_CUSTOMER_RESPONSE || $order->customer_id != $customerID) {
            return $this->responseError();
        }

        Event::dispatch('app.order.customer_reponse_changes_reponse', $order);

        if ($data['action'] == 'cancel') {
            Event::dispatch('order.actual_logs', [$order, OrderLogsActual::ORDER_CUSTOMER_CANCELLED]);
            // then, Cancel Order
            $this->orderRepository->updateOrderStatus($order, Order::STATUS_CANCELLED_FOR_ITEMS);

            logOrderActionsInCache($order->id, 'customer_rejected_changes');

            // Run Cancellation Process to Update Inventory
            CustomerCancelledOrder::dispatch($order);

            return $this->responseSuccess();
        }

        Event::dispatch('order.actual_logs', [$order, OrderLogsActual::ORDER_CUSTOMER_ACCEPTED]);

        logOrderActionsInCache($order->id, 'customer_accept_changes');

        // then, Return the order to Pending
        $this->orderRepository->updateOrderStatus($order, Order::STATUS_PENDING);

        // if Customer Accept Changes
        CustomerAcceptedOrderChanges::dispatch($order);

        // Call the function that Handle given Response
        // $this->orderRepository->customerOrderChangesResponse($order, $data);

        return $this->responseSuccess();
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function customerCancelOrder(CustomerCancelOrderRequest $request)
    {
        $data = $request->only(['order_id']);
        $customerID = auth('customer')->id();

        // Get the Order
        $order = $this->orderRepository->findOrFail($data['order_id']);

        // first of all, check that the order still in pending
        $availableStatus = [Order::STATUS_PENDING, Order::STATUS_WAITING_CUSTOMER_RESPONSE, Order::STATUS_PREPARING, Order::STATUS_SCHEDULED];
        if (!in_array($order->status, $availableStatus) || $order->customer_id != $customerID) {
            return $this->responseError();
        }

        // Call the function that cancel the Order
        $order = $this->orderRepository->customerCancelOrder($order);

        // send notification to operation manager && area manager
        $payload['model'] = $order;
        $payload['area'] = $order->area;

        Event::dispatch('admin.alert.admin_cancelled_order', [auth('customer')->user(), $payload]);
        Event::dispatch('driver.order-cancelled', $order->id);

        return $this->responseSuccess(new OrderDetailsSingle($order));
    }

    /**
     * @param array $items
     * @return array
     */
    private function prepareItemsForResponse(Order $order, array $items)
    {
        $orderItems = $order->items->where('qty_shipped', '>', 0);

        // Prepare Items for each type
        $notEnough = $this->prepareNotEnoughItems($orderItems, $items);
        $outOfStock = $this->prepareOutOfStockItems($orderItems, $items);
        $availableItems = $this->prepareAvailableItems($orderItems, $notEnough, $outOfStock);

        // Handle Payment Summary
        $cartItems = $this->handleItemsForCart($notEnough, $outOfStock, $availableItems);

        // Data Required to calculate payment summary
        $data = [
            'items' => $cartItems,
            'customer' => $order->customer,
            'promo_code' => $order->promotion_id ? Promotion::find($order->promotion_id) : null,
        ];

        // Calculate Payment Summary
        $paymentSummary = $this->paymentSummary($data, $order);

        return [
            'not_enough'    =>  $notEnough,
            'out_of_stock'    =>  $outOfStock,
            'available_items'    =>  $availableItems,
            'payment_summary'    =>  $paymentSummary,
        ];
    }

    /**
     * @param Collection $orderItems
     * @param array $items
     * 
     * @return array
     */
    private function prepareNotEnoughItems(Collection $orderItems, array $items)
    {
        if (empty($items['not_enough'])) {
            return [];
        }

        $outOfStockItemsCollection = collect($items['out_of_stock'])->pluck('product_id')->toArray();
        $notEnoughItems = [];
        $notEnoughItemsCollection = collect($items['not_enough'])->pluck('product_id')->toArray();
        $allItems = array_merge($notEnoughItemsCollection, $outOfStockItemsCollection);
        $allItemsFromDB = Product::whereIn('id', $allItems)->get();

        // Prepare Not enough Product Data
        foreach ($items['not_enough'] as $item) {
            $product = $allItemsFromDB->where('id', $item['product_id'])->first();

            $notEnoughItems[] = [
                'id'                    => $product->id,
                'image_url'             => $product->image_url,
                'thumb_url'             => $product->thumb_url,
                'price'                 => $product->price,
                'unit_name'             => $product->unit->name,
                'unit_value'            => $product->unit_value,
                'name'                  => $product->name,
                'qty_ordered'           => $orderItems->where('product_id', $product->id)->first()->qty_ordered,
                'available_qty'        => $item['available_qty'],
            ];
        }

        return $notEnoughItems;
    }

    /**
     * @param Collection $orderItems
     * @param array $items
     * 
     * @return array
     */
    private function prepareOutOfStockItems(Collection $orderItems, array $items)
    {
        if (empty($items['out_of_stock'])) {
            return [];
        }

        $outOfStockItems = [];
        $outOfStockItemsCollection = collect($items['out_of_stock'])->pluck('product_id')->toArray();
        $notEnoughItemsCollection = collect($items['not_enough'])->pluck('product_id')->toArray();
        $allItems = array_merge($notEnoughItemsCollection, $outOfStockItemsCollection);
        $allItemsFromDB = Product::whereIn('id', $allItems)->get();

        // Prepare out of stock Product Data
        foreach ($items['out_of_stock'] as $item) {
            $product = $allItemsFromDB->where('id', $item['product_id'])->first();

            $outOfStockItems[] = [
                'id'                    => $product->id,
                'image_url'             => $product->image_url,
                'thumb_url'             => $product->thumb_url,
                'price'                 => $product->price,
                'unit_name'             => $product->unit->name,
                'unit_value'            => $product->unit_value,
                'name'                  => $product->name,
                'qty_ordered'           => $orderItems->where('product_id', $product->id)->first()->qty_ordered
            ];
        }
        return $outOfStockItems;
    }


    /**
     * @param Collection $orderItems
     * @param array $notEnough
     * @param array $outOfStock
     * 
     * @return array
     */
    private function prepareAvailableItems(Collection $orderItems, array $notEnough, array $outOfStock)
    {
        $availableItems = [];
        $outProducts = array_merge(array_column($outOfStock, 'id'), array_column($notEnough, 'id'));

        $validOrderItems = $orderItems->whereNotIn('product_id', $outProducts);
        $validOrderItemsIDs = $orderItems->whereNotIn('product_id', $outProducts)->pluck('product_id')->toArray();

        $allItemsFromDB = Product::whereIn('id', $validOrderItemsIDs)->get();

        // Prepare Not enough Product Data
        foreach ($validOrderItems as $item) {
            $product = $allItemsFromDB->where('id', $item->product_id)->first();

            $availableItems[] = [
                'id'                    => $product->id,
                'image_url'             => $product->image_url,
                'thumb_url'             => $product->thumb_url,
                'price'                 => $product->price,
                'unit_name'             => $product->unit->name,
                'unit_value'            => $product->unit_value,
                'name'                  => $product->name,
                'qty_ordered'           => $item->qty_ordered
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
    private function handleItemsForCart(array $notEnough, array $outOfStock, array $availableItems)
    {
        $items = [];
        foreach ($notEnough as $item) {
            $items[] = [
                'id'                    => $item['id'],
                'qty'                    => (int) $item['available_qty'],
                'price'                    => (int) $item['price'],
            ];
        }

        foreach ($outOfStock as $item) {
            $items[] = [
                'id'                    => $item['id'],
                'qty'                    => 0,
                'price'                    => (int) $item['price'],
            ];
        }

        foreach ($availableItems as $item) {
            $items[] = [
                'id'                    => $item['id'],
                'qty'                    => (int) $item['qty_ordered'],
                'price'                    => (int) $item['price'],
            ];
        }

        return $items;
    }

    /** Calculate Payment Summary
     * @param array $data
     * @return array
     */
    public function paymentSummary(array $data, Order $order)
    {
        $deliver_fees = config('robosto.DELIVERY_CHARGS');
        $total = 0;

        foreach ($data['items'] as $item) {
            $total += $item['price'] * $item['qty'];
        }

        $amountToPay = $total + $deliver_fees;
        $customerWallet = 0;

        // Apply Wallet if user Exist
        if (auth('customer')->check()) {
            $customerWallet = auth('customer')->user()->wallet;
            $amountToPay -= $customerWallet;
        }

        if ($amountToPay < 0) {
            $amountToPay = 0;
        }

        $summary = [
            'basket_total'      =>  $total,
            'delivery_fees'     =>  (int) $deliver_fees,
            'balance'           =>  (float) $customerWallet,
            'amount_to_pay'     =>  $amountToPay
        ];

        // Handle Promotion Code
        $summary = $this->handlePromoCode($data, $summary);

        // Handle Referral Code on the First Order
        $summary = $this->handleCustomerFirstOrder($data, $summary);

        $summary['amount_to_pay'] += $order->customer_balance;

        // Handle Long Decimal Numbers
        $summary['amount_to_pay'] = round($summary['amount_to_pay'], 2);
        if (isset($summary['discount'])) {
            $summary['discount'] = round($summary['discount'], 2);
        }

        return $summary;
    }


    /**
     * Handle Promotion Code
     * 
     * @param array $data
     * @param array $summary
     * 
     * @return array
     */
    public function handlePromoCode(array $data, array $summary)
    {
        $total = $summary['basket_total'];
        $amountToPay = $summary['amount_to_pay'];
        // Handle Promotion Code
        if (isset($data['promo_code'])) {
            // Get the Promotion
            $promotion = $data['promo_code'];
            if ($promotion->discount_type == Promotion::DISCOUNT_TYPE_VALUE) { // 10 L.E
                $discount = $promotion->discount_value;
            } else {
                $discount = ($promotion->discount_value / 100) * $total;
            }

            if ($amountToPay > $discount) {
                $summary['discount']  = $discount;
                $summary['amount_to_pay']  -= $discount;
            } else {
                $summary['discount']  = $discount;
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
    public function handleCustomerFirstOrder(array $data, array $summary)
    {
        // Handle Referral Code on the First Order
        if (isset($data['customer'])) {

            $customer = $data['customer'];
            // if the first order
            if ($customer->invitation_applied == 0 && !is_null($customer->invited_by)) {

                // Apply 25% Discount on the Order Total
                $percentage = config('robosto.ORDER_INVITE_CODE_GIFT');
                $discount = ($percentage / 100) * $summary['amount_to_pay'];

                $summary['discount']  = $discount;
                $summary['coupon']  = $customer->invitedBy->referral_code;
                $summary['amount_to_pay'] -= $discount;
            }
        }

        return $summary;
    }
}
