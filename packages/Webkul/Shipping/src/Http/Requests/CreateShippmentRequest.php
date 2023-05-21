<?php

namespace Webkul\Shipping\Http\Requests;

use App\Http\Requests\ApiBaseRequest;


class CreateShippmentRequest extends ApiBaseRequest
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
            'merchant'=>'nullable',
            'customer_name' => 'required',
            'customer_phone' => 'required',
            'customer_email' => 'nullable',
            'customer_address' => 'required',
            'customer_landmark'=>'nullable',
            'customer_apartment_no'=>'nullable',
            'customer_building_no'=>'nullable',
            'customer_floor_no'=>'nullable',
            'pickup_id' => 'required',
            'items_count' => 'required|numeric',
            'price' => 'required|numeric',
            'note' => 'nullable',
            'description' => 'nullable'
        ];
    }
}