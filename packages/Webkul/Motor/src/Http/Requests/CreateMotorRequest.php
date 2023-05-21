<?php

namespace Webkul\Motor\Http\Requests;

use App\Http\Requests\ApiBaseRequest;


class CreateMotorRequest extends ApiBaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {

        return  [
            'chassis_no' => 'required|unique:motors',
            'license_plate_no' => 'required|unique:motors,license_plate_no'
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
