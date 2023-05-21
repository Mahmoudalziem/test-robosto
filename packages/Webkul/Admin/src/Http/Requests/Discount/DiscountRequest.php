<?php

namespace Webkul\Admin\Http\Requests\Discount;

use App\Http\Requests\ApiBaseRequest;
use Webkul\Inventory\Models\InventoryArea;

class DiscountRequest extends ApiBaseRequest {

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize() {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules() {

        $rules = [
            //"area_id" => 'nullable|exists:areas,id',
            'product_id' => 'required|exists:products,id',
            'start_validity' => 'required',
            'end_validity' => 'required',
            'discount_type' => 'required',
            'discount_value' => 'required',
        ];

        $stock = InventoryArea::where(['product_id' => $this->product_id])
                        ->whereIn('area_id', $this->area_id)
                        ->pluck('total_qty')->toArray();

        $rules['discount_qty'] = 'nullable|integer|between:1,' . min($stock);
        // In Update
//        if (isset($this->id)) {
// 
//        }

        return $rules;
    }

}
