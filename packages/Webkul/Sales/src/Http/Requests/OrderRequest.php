<?php

namespace Webkul\Sales\Http\Requests;

use App\Http\Requests\ApiBaseRequest;
use Illuminate\Foundation\Http\FormRequest;

class OrderRequest extends ApiBaseRequest
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
            'address_id'             =>  'required|numeric|exists:customer_addresses,id,deleted_at,NULL',
            'payment_method_id'      =>  'required|numeric|exists:payment_methods,id',
            'promo_code'             =>  'nullable|exists:promotions,promo_code,deleted_at,NULL',
            'scheduled_at'           =>  'nullable',
            'card_id'                =>  'nullable|numeric|exists:paymob_cards,id',
        ];
    }
}