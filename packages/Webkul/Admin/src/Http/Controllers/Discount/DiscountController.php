<?php

namespace Webkul\Admin\Http\Controllers\Discount;

use Illuminate\Http\Request;
use Webkul\Core\Http\Controllers\BackendBaseController;
use Illuminate\Support\Facades\Event;
use Webkul\Discount\Models\Discount;
use Webkul\Admin\Repositories\Discount\DiscountRepository;
use Webkul\Admin\Http\Requests\Discount\DiscountRequest;
use Webkul\Admin\Http\Resources\Discount\DiscountSingle;
use Webkul\Admin\Http\Resources\Discount\DiscountAll;
use function DeepCopy\deep_copy;

class DiscountController extends BackendBaseController {

    protected $discountRepository;

    public function __construct(DiscountRepository $discountRepository) {
        $this->discountRepository = $discountRepository;
    }

    public function list(Request $request) {

        $discount = $this->discountRepository->list($request);
        $data = new DiscountAll($discount);
        return $this->responsePaginatedSuccess($data, null, $request);
    }


    public function create(DiscountRequest $request) {

        $discount = $this->discountRepository->create($request->all());
        $data = new DiscountSingle($discount);

        Event::dispatch('admin.discount.created', $discount);
        Event::dispatch('admin.log.activity', ['create', 'discount', $discount, auth('admin')->user(), $discount]);
        
        return $this->responseSuccess($data, 'New Discount has been created!');
    }

    public function show(Discount $discount)
    {
        $discount = new DiscountSingle($discount);
        return $this->responseSuccess($discount);
    }

    public function update($id, DiscountRequest $request) {

        $discount = $this->discountRepository->findOrFail($id);
        $before = deep_copy($discount);

        $discount = $this->discountRepository->update($request->all(), $discount);

        Event::dispatch('admin.log.activity', ['update', 'discount', $discount, auth('admin')->user(), $discount, $before]);

        return $this->responseSuccess(null, "Discount has been updated!");
    }

    public function setStatus(Discount $discount, Request $request) {
        $this->validate($request, [
            'status' => 'required|numeric|in:0,1',
        ]);

        $before = deep_copy($discount);

        $discount = $this->discountRepository->setStatus($discount, $request->only('status'));
        
        Event::dispatch('admin.log.activity', ['update-status', 'discount', $discount, auth('admin')->user(), $discount, $before]);
        Event::dispatch('admin.discount.set-status', $discount);

        return $this->responseSuccess();
    }

}
