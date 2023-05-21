<?php

namespace Webkul\Admin\Http\Requests\Customer;

use App\Http\Requests\ApiBaseRequest;

class CallcenterProfileUpdateRequest extends ApiBaseRequest
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
            'name' => 'required',
            'phone' => ['required','regex:/^(01)(0|1|2|5)[0-9]{8}$/'], // this is mobile regex
            'landline' =>  'nullable|regex:/^(0)[1-9]{1}[0-9]{8}$/',
            'addressInfo.area_id' => 'required|exists:areas,id',
            'addressInfo.name' => 'required|string|max:20',
            'addressInfo.address' => 'required',
            'addressInfo.building_no' => 'required|string',
            'addressInfo.floor_no' => 'required|string',
            'addressInfo.apartment_no' => 'required|string',
            'addressInfo.location.lat' => 'required|numeric',
            'addressInfo.location.lng' => 'required|numeric',
        ];
    }
}