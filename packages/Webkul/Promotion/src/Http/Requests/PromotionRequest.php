<?php

namespace Webkul\Promotion\Http\Requests;

use App\Http\Requests\ApiBaseRequest;
use Illuminate\Foundation\Http\FormRequest;

class PromotionRequest extends ApiBaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return  [
            'promo_code' => 'required|exists:promotions,promo_code',
            'items' => 'required|array',
            'items.*.id'             =>  'required|numeric|min:1',
            'items.*.qty'            =>  'required|numeric|min:1',
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
