<?php

namespace Webkul\Shipping\Http\Requests;

use App\Http\Requests\ApiBaseRequest;

class CreateShipperRequest extends ApiBaseRequest
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
            'name'  =>  'required',
            'email'     =>  'required|unique:shippers,email',
            'password'    =>  'required',
        ];
    }
}