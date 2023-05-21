<?php

namespace Webkul\Admin\Http\Requests\Sales;

use App\Http\Requests\ApiBaseRequest;

class UpdateOrderRequest extends ApiBaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'items'                  =>  'required|array|min:1',
            'items.*.id'             =>  'required|numeric|exists:products,id,deleted_at,NULL',
            'items.*.qty'            =>  'required|numeric|min:1',
            'promo_code'             =>  'nullable|exists:promotions,promo_code,deleted_at,NULL',
        ];
    }
}