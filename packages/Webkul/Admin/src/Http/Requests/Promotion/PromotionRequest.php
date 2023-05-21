<?php

namespace Webkul\Admin\Http\Requests\Promotion;

use App\Http\Requests\ApiBaseRequest;
use Illuminate\Validation\Rule;
use Webkul\Core\Rules\ImageBase64;

class PromotionRequest extends ApiBaseRequest
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
        $rules = [
            'areas' =>['required', 'exists:areas,id'] ,//'required|exists:areas,id',
            'tags' =>['required', 'exists:tags,id'] ,
            'promo_code'=> 'required|unique:promotions,promo_code',
            'discount_type'=> 'required'  ,
            'discount_value'=> 'required|integer|min:1'  ,
            'total_vouchers'=> 'required|integer|min:1'  ,
            'total_redeems_allowed'=> 'required|integer|min:0'  ,
            'price_applied'=> 'required'  ,
            'minimum_order_amount'=> 'nullable|numeric|min:0'  ,
            'minimum_items_quantity' => 'nullable|integer|min:0'  ,
            'apply_type'=> 'required',
            'apply_content'=> 'required',
            'exceptions_items'=> 'nullable'

        ];

        foreach (core()->getAllLocales() as $locale) {
                $rules[$locale->code. '.' . 'title'] =  'required|string|min:2';
                $rules[$locale->code. '.' . 'description'] =  'required|string|min:2';
        }

        // In Update
        if (isset($this->id) ) {

            $rules['promo_code'] = 'required|unique:promotions,promo_code,'.$this->id;

            foreach (core()->getAllLocales() as $locale) {
                $rules[$locale->code. '.' . 'title'] =  'required|string|min:2';
                $rules[$locale->code. '.' . 'description'] =  'required|string|min:2';
            }
        }

        return $rules;
    }
}