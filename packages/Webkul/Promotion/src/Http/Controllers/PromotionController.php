<?php

namespace Webkul\Promotion\Http\Controllers;

use Illuminate\Http\Request;
use Webkul\Area\Models\Area;
use Illuminate\Http\JsonResponse;
use Webkul\Product\Models\Product;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Event;
use App\Exceptions\PromotionValidationException;
use Webkul\Promotion\Http\Resources\PromotionAll;
use Webkul\Promotion\Http\Requests\PromotionRequest;
use Webkul\Promotion\Repositories\PromotionRepository;
use Webkul\Core\Http\Controllers\BackendBaseController;
use Webkul\Promotion\Services\ApplyPromotion\ApplyPromotion;
use Webkul\Promotion\Services\PromotionValidation\CheckPromotion;
use Webkul\Promotion\Services\PromotionValidation\Rules\Available;
use Webkul\Promotion\Services\PromotionValidation\Rules\ValidDate;
use Webkul\Promotion\Http\Resources\Promotion as PromotionResource;
use Webkul\Promotion\Services\PromotionValidation\Rules\CustomerArea;
use Webkul\Promotion\Services\PromotionValidation\Rules\CustomerTags;
use Webkul\Promotion\Services\PromotionValidation\Rules\VouchersCount;
use Webkul\Promotion\Services\PromotionValidation\Rules\RedeemsAllowed;
use Webkul\Promotion\Services\PromotionValidation\Rules\MinimumOrderRequirements;
use Webkul\Promotion\Services\PromotionValidation\Rules\MaxAllowedDevice;
use Webkul\Customer\Models\Customer;

class PromotionController extends BackendBaseController
{

    /**
     * PromotionRepository object
     *
     * @var PromotionRepository
     */
    protected $promotionRepository;

    /**
     * Create a new controller instance.
     *
     * @param PromotionRepository $promotionRepository
     * @return void
     */
    public function __construct(PromotionRepository $promotionRepository)
    {
        $this->promotionRepository = $promotionRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        $promotions = $this->promotionRepository->list($request);

        Event::dispatch('app-promotions.fetched', $promotions);

        $data = new PromotionAll($promotions);

        return $this->responseSuccess($data);
    }

    /**
     * Show the specified promotion.
     *
     * @param PromotionRequest $request
     * @return JsonResponse
     */
    public function checkPromotion(PromotionRequest $request)
    {
        $data = $request->only(['promo_code', 'items']);
        $customer = auth('customer')->user();
        $data['area_id'] = $request->header('area') ?? Area::where('default', 1)->first()->id;
        $data['deviceid'] = $request->header('deviceid') ?? null;

        // Save Customer Device
        $this->saveCustomerDevice($customer, $data['deviceid']);

        // First of all, Check that this is not the first order for the customer
        if ($customer->invitation_applied == 0 && !is_null($customer->invited_by)) {
            if ($customer->activeOrders->isEmpty()) {
                return $this->responseError(422, __('customer::app.promoNotWithFirstOrder'));
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
            ->setNext(new MinimumOrderRequirements($this->totalItemPrice($data['items']), $this->sumItemsQty($data['items'])))
            ->setNext(new CustomerArea($customer, $data['area_id']))
            ->setNext(new CustomerTags($customer))
            ->setNext(new RedeemsAllowed($customer));

        // Start Chaining
        $checkPromotion = new CheckPromotion($promotion);
        $checkPromotion->setRule($rule);
        $checkPromotion->checkPromotionIsValid();

        // In Case: Promotion is Valid
        $applyPromotion = new ApplyPromotion($promotion, $data['items']);
        $products = $applyPromotion->apply();

        // Check if the Promotion is Valid, but the given items are Not Applicable
        if (!count($products['discounted_items'])) {
            throw new PromotionValidationException(406, __('customer::app.notValidPromoCode'));
        }

        return $this->responseSuccess($products);
    }

    /** Calculate Payment Summary
     *
     * @param array $data
     * @return float
     */
    private function totalItemPrice(array $items)
    {
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
    private function sumItemsQty(array $items)
    {
        $total = 0;

        foreach ($items as $item) {
            $total += $item['qty'];
        }

        return $total;
    }

    private function extraPromotionRules($promotion)
    {
        $extraPromotionRules = collect(config('robosto.EXTERA_PROMOTOION_RULES'));
        $extraPromotionCollection = $extraPromotionRules->where('promo_code_id', $promotion->id)->first();
        if ($extraPromotionCollection) {
            $promotion->max_item_qty = isset($extraPromotionCollection['max_item_qty']) ? $extraPromotionCollection['max_item_qty'] : null;
            $promotion->excluded_from_categories_offer = isset($extraPromotionCollection['excluded_from_categories_offer']) ? $extraPromotionCollection['excluded_from_categories_offer'] : null;
            $promotion->max_device_count = isset($extraPromotionCollection['max_device_count']) ? $extraPromotionCollection['max_device_count'] : null;
        }

        return $promotion;
    }

    private function saveCustomerDevice(Customer $customer, $deviceid)
    {
        //(customer and device is Composite unique)
        $device = $customer->devices()->where('deviceid', $deviceid)->first();
        if (!$device) {
            $customer->devices()->create(['deviceid' => $deviceid]);
        }
    }
}