<?php

namespace Webkul\Admin\Http\Requests\Customer;

use App\Http\Requests\ApiBaseRequest;

class CustomerUpdateRequest extends ApiBaseRequest
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
            'gender' => 'required',
            'phone' => ['required','unique:customers,phone,'. $this->route('customer')->id,'regex:/^(01)(0|1|2|5)[0-9]{8}$/'], // this is mobile regex
            'email'             => 'nullable|email|unique:customers,email,' . $this->id,
            'landline' =>  'nullable|regex:/^(0)[1-9]{1}[0-9]{8}$/',
        ];
    }
}