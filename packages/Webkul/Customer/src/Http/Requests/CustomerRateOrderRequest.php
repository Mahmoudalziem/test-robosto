<?php

namespace Webkul\Customer\Http\Requests;

use App\Http\Requests\ApiBaseRequest;

class CustomerRateOrderRequest extends ApiBaseRequest
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
            'order_id'      =>  'required|numeric',
            'rating'        =>  'required|numeric|min:1|max:5',
            'comment'       =>  'nullable|string',
        ];
    }
}