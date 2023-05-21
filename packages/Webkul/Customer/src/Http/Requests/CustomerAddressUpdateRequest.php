<?php

namespace Webkul\Customer\Http\Requests;

use App\Http\Requests\ApiBaseRequest;


class CustomerAddressUpdateRequest extends ApiBaseRequest
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
            'area_id' => 'required|numeric',
            'icon_id' => 'required|numeric',
            'name' => 'required|string|max:20',
            'address' => 'required',
            'building_no' => 'required|string',
            'apartment_no' => 'required|string',
            'floor_no' => 'required|string',
            'location.lat' => 'required|numeric',
            'location.lng' => 'required|numeric',
            'phone' => ['nullable','regex:/^(01)(0|1|2|5)[0-9]{8}$/'], // this is mobile regex

        ];
    }
}