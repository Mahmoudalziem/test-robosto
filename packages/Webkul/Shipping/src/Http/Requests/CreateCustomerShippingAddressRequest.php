<?php

namespace Webkul\Shipping\Http\Requests;

use App\Http\Requests\ApiBaseRequest;


class CreateCustomerShippingAddressRequest extends ApiBaseRequest
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
            'tracking_number' => 'required',
            'phone_number' => 'required',
            'address' => 'required',
            'location.lat' => 'required|numeric',
            'location.lng' => 'required|numeric',
            'scheduled_at'=>'required|date'
        ];
    }
}
