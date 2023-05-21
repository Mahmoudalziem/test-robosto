<?php

namespace Webkul\Product\Http\Controllers;

use App\Enums\TrackingUserEvents;
use Illuminate\Http\Request;
use Webkul\Area\Models\Area;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Webkul\Product\Models\Product;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Event;
use Webkul\Promotion\Models\Promotion;
use Webkul\Product\Http\Resources\ProductAll;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Category\Repositories\SubCategoryRepository;
use Webkul\Core\Http\Controllers\BackendBaseController;
use Webkul\Product\Http\Requests\PaymentSummaryRequest;
use Webkul\Product\Http\Resources\Product as ProductResource;
use Webkul\Promotion\Services\PromotionValidation\CheckPromotion;
use Webkul\Promotion\Services\PromotionValidation\Rules\Available;
use Webkul\Promotion\Services\PromotionValidation\Rules\ValidDate;
use Webkul\Promotion\Services\PromotionValidation\Rules\CustomerArea;
use Webkul\Promotion\Services\PromotionValidation\Rules\CustomerTags;
use Webkul\Promotion\Services\PromotionValidation\Rules\VouchersCount;
use Webkul\Promotion\Services\PromotionValidation\Rules\RedeemsAllowed;
use Webkul\Promotion\Services\PromotionValidation\Rules\MinimumOrderRequirements;
use Webkul\Promotion\Services\PromotionValidation\Rules\MaxAllowedDevice;

class ProductController extends BackendBaseController {

    /**
     * ProductRepository object
     *
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * Create a new controller instance.
     *
     * @param ProductRepository $productRepository
     * @return void
     */
    public function __construct(ProductRepository $productRepository) {
        $this->productRepository = $productRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index() {
        $products = $this->productRepository->all();

        Event::dispatch('app-products.fetched', $products);

        return $this->responseSuccess($products);
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function popular(Request $request) {
        $products = $this->productRepository->active()->hasAmount()->limit(6)->orderBy('sold_count', 'DESC')->get();

        Event::dispatch('app-products.popular', $products);

        $data = new ProductAll($products);

        return $this->responseSuccess($data);
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function newArrivals(Request $request) {
        $products = $this->productRepository->orderBy('created_at', 'DESC')->active()->hasAmount()->limit(15)->get();

        Event::dispatch('app-products.popular', $products);

        $data = new ProductAll($products);

        return $this->responseSuccess($data);
    }

    /**
     * Search for Products.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request) {
        $products = $this->productRepository->search($request);

        $data = new ProductAll($products);

        Event::dispatch('app-products.popular', $products);

        if (auth('customer')->check()) {
            $data = ['server' => $request->server(), 'request_data' => $request->all()];

            Event::dispatch('tracking.user.event', [TrackingUserEvents::SEARCH, auth('customer')->user(), $data]);
        }

        return $this->responsePaginatedSuccess($data, null, $request);
    }

    /**
     * Get products by sub category.
     *
     * *
     * @param Request $request
     * @param SubCategoryRepository $subCategoryRepository
     * @param int $id
     * @return JsonResponse
     */
    public function getProductsBySubCategory(Request $request, SubCategoryRepository $subCategoryRepository, $id) {
        // Find SubCategory
        $subCategory = $subCategoryRepository->findOrFail($id);

        $products = $this->productRepository->productsBySubCategory($request, $subCategory);

        $data = new ProductAll($products);

        return $this->responsePaginatedSuccess($data, null, $request);
    }

    /**
     * Show the specified product.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show($id) {
        $product = $this->productRepository->with('subCategories')->findOrFail($id);

        if (auth('customer')->check()) {
            Event::dispatch('app-products.show', [$product, auth('customer')->user()]);
        }

        $data = new ProductResource($product);

        return $this->responseSuccess($data);
    }

    /** Calculate Payment Summary
     * @param Request $request
     * @return JsonResponse
     */
    public function paymentSummary(PaymentSummaryRequest $request) {
        $data = $request->only(['items', 'promo_code']);
        $data['area_id'] = $request->header('area') ?? Area::where('default', 1)->first()->id;
        $data['deviceid'] = $request->header('deviceid') ?? null;
        $promotion = null;
        $firstOrder = false;
        if(isset($request->payment_method_id) && $request->payment_method_id ==3){
            $data['items'] = $this->addMarginToItems($data['items']);
        }
        if (auth('customer')->check()) {

            $customer = auth('customer')->user();
            $data['customer'] = $customer;

            $this->checkFreeShippingCoupon($data);

            // Check this is the first order for this customer and he has register by invitation code
            if ($customer->invitation_applied == 0 && !is_null($customer->invited_by)) {
                if ($customer->activeOrders->isEmpty()) {
                    Log::info('ENTERED');
                    unset($data['promo_code']);
                }
            }
            if(isset($data['promo_code']) && !empty($data['promo_code'])){
                $promotion = Promotion::where('promo_code', $data['promo_code'])->first();
                if(!$promotion){
                    Log::info('ENTERED 2');
                    unset($data['promo_code']);
                }
            }
            Log::info($data);

            if (isset($data['promo_code']) && !empty($data['promo_code'])) {
                // Get the Promotion
                $promotion = Promotion::where('promo_code', $data['promo_code'])->first();

                // implement extra promotion rules
                if (config('robosto.EXTERA_PROMOTOION_RULES')) {
                    $promotion = $this->extraPromotionRules($promotion);
                }

                // Define Validation Rules
                $rule = new Available();
                $rule->setNext(new ValidDate())
                        ->setNext(new MaxAllowedDevice($data['deviceid']))
                        ->setNext(new VouchersCount())
                        ->setNext(new MinimumOrderRequirements($this->totalItemPrice($data['items']), $this->sumItemsQty($data['items'])))
                        ->setNext(new CustomerArea($customer, $data['area_id']))
                        ->setNext(new CustomerTags($customer))
                        ->setNext(new RedeemsAllowed($customer));

                // Start Chaining
                $checkPromotion = new CheckPromotion($promotion);
                $checkPromotion->setRule($rule);
                $checkPromotion->checkPromotionIsValid();
            }

            // if this new customer and used invitation code
            if ($customer->invitation_applied == 0 && !is_null($customer->invited_by)) {
                $firstOrder = true;
                if ($customer->activeOrders->isNotEmpty()) {
                    $firstOrder = false;
                }
            }
        }

        $summary = $this->productRepository->paymentSummary($data, $promotion);

        $summary['first_order'] = $firstOrder;
        if (isset($data['free_shipping']) && $data['free_shipping']) {
            $summary['coupon'] = config('robosto.FREE_SHIPPING_COUPON');
            $summary['discount'] = config('robosto.DELIVERY_CHARGS');
        }

        // if (auth('customer')->check()) {
        // $data = ['server' => $request->server(), 'request_data' => $request->all()];
        // Event::dispatch('tracking.user.event', [TrackingUserEvents::INITIATE_CHECKOUT, auth('customer')->user(), $data]);
        // Event::dispatch('tracking.user.event.items', [TrackingUserEvents::ADD_TO_CART, auth('customer')->user(), $data]);
        //  }


        return $this->responseSuccess($summary);
    }
    public function addMarginToItems(array $items) {
        for($i=0;$i<count($items); $i++){
            $items[$i]['margin']= config('robosto.BNPL_INTEREST');
        }
        return $items;
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

    /** Calculate Payment Summary
     *
     * @param array $data
     * @return float
     */
    private function totalItemPrice(array $items) {
        $productsFromDB = Product::whereIn('id', array_column($items, 'id'))->get();
        $total = 0;

        foreach ($items as $item) {
            $product = $productsFromDB->where('id', $item['id'])->first();

            $total += $product->tax + ($product->price * $item['qty']);
        }

        return $total;
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

    private function extraPromotionRules($promotion) {
        $extraPromotionRules = collect(config('robosto.EXTERA_PROMOTOION_RULES'));
        $extraPromotionCollection = $extraPromotionRules->where('promo_code_id', $promotion->id)->first();
        if ($extraPromotionCollection) {
            $promotion->max_item_qty = isset($extraPromotionCollection['max_item_qty']) ? $extraPromotionCollection['max_item_qty'] : null;
            $promotion->excluded_from_categories_offer = isset($extraPromotionCollection['excluded_from_categories_offer']) ? $extraPromotionCollection['excluded_from_categories_offer'] : null;
            $promotion->max_device_count = isset($extraPromotionCollection['max_device_count']) ? $extraPromotionCollection['max_device_count'] : null;
        }

        return $promotion;
    }

}
