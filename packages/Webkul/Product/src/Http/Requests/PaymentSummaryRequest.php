<?php

namespace Webkul\Product\Http\Requests;

use App\Http\Requests\ApiBaseRequest;

class PaymentSummaryRequest extends ApiBaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return  [
            'items'                  =>  'required|array|min:1',
            'items.*.id'             =>  'required|numeric|exists:products,id',
            'items.*.qty'            =>  'required|numeric',
            // 'promo_code'             =>  'nullable|exists:promotions,promo_code',
            'promo_code'             =>  'nullable',
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }
}
