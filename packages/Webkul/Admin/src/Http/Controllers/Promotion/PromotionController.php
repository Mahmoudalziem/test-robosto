<?php

namespace Webkul\Admin\Http\Controllers\Promotion;

use Illuminate\Http\Request;
use App\Jobs\TriggerFCMRTDBJob;
use function DeepCopy\deep_copy;
use Illuminate\Support\Facades\Event;
use App\Jobs\StorePromotionExceptions;
use Webkul\Admin\Http\Resources\Promotion\PromotionAll;
use Webkul\Admin\Http\Resources\Sales\OrderAll;
use Webkul\Core\Http\Controllers\BackendBaseController;
use Webkul\Admin\Http\Requests\Promotion\PromotionRequest;
use Webkul\Admin\Http\Resources\Promotion\PromotionSingle;
use Webkul\Admin\Repositories\Promotion\PromotionRepository;
use Webkul\Admin\Http\Resources\Promotion\PromotionCustomers;
use Webkul\Sales\Models\Order;

class PromotionController extends BackendBaseController
{

    protected $promotionRepository;

    public function __construct(PromotionRepository $promotionRepository)
    {
        $this->promotionRepository = $promotionRepository;
    }

    public function list(Request  $request)
    {
        $promotions = $this->promotionRepository->list($request);
        $data = new PromotionAll($promotions);
        return $this->responsePaginatedSuccess($data, null, $request);
    }


    public function promotionCustomers($id)
    {
        $promotion = $this->promotionRepository->findOrFail($id);

        $data = new PromotionCustomers($promotion->customers);

        return $this->responsePaginatedSuccess($data, null, $data);
    }

    public function promotionOrders($id)
    {
        $promotion = $this->promotionRepository->findOrFail($id);
        $promotionOrders = Order::where('promotion_id', $promotion->id)->orderBy('id', 'desc')->paginate(10);

        $data = new OrderAll($promotionOrders);

        return $this->responsePaginatedSuccess($data, null, $data);
    }


    public function create(PromotionRequest $request)
    {
        $data = $request->only('areas', 'tags', 'ar', 'en', 'title', 'description', 'promo_code', 'discount_type', 'discount_value', 'start_validity', 'end_validity', 'total_vouchers', 'minimum_order_amount', 'minimum_items_quantity', 'total_redeems_allowed', 'price_applied', 'apply_type', 'apply_content', 'exceptions_items', 'send_notifications', 'show_in_app', 'sms_content');

        $promotion = $this->promotionRepository->create($data);

        Event::dispatch('admin.promotion.created', $promotion);
        Event::dispatch('admin.log.activity', ['create', 'promotion', $promotion, auth('admin')->user(), $promotion]);

        // Run Job to save exception items
        if (!is_null($data['exceptions_items'])) {
            StorePromotionExceptions::dispatch($promotion);
        }

        return $this->responseSuccess($promotion);
    }

    public function show($id)
    {
        $promotion = $this->promotionRepository->findOrFail($id);
        $promotion = new PromotionSingle($promotion);
        return $this->responseSuccess($promotion);
    }

    public function update($id, PromotionRequest  $request)
    {
        $data = $request->except(['apply_type', 'apply_content', 'exceptions_items']);
        $data['is_valid'] = 1;

        $promotion = $this->promotionRepository->with('translations')->findOrFail($id);
        $before = deep_copy($promotion);

        $promotion = $this->promotionRepository->update($data, $promotion);

        TriggerFCMRTDBJob::dispatch('updated', 'promotions');

        Event::dispatch('admin.log.activity', ['update', 'promotion', $promotion, auth('admin')->user(), $promotion, $before]);

        return $this->responseSuccess(null, "promotion has been updated!");
    }

    public function setStatus($id, Request $request)
    {

        $this->validate($request, [
            'status'    =>  'required|numeric|in:0,1',
        ]);

        $promotion = $this->promotionRepository->with('translations')->findOrFail($id);
        $before = deep_copy($promotion);

        $promotion = $this->promotionRepository->setStatus($promotion, $request->only('status'));

        TriggerFCMRTDBJob::dispatch('updated', 'promotions');

        Event::dispatch('admin.promotion.set-status', $promotion);
        Event::dispatch('admin.log.activity', ['update-status', 'promotion', $promotion, auth('admin')->user(), $promotion, $before]);

        return $this->responseSuccess($promotion);
    }
}