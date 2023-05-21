<?php

namespace Webkul\Customer\Http\Requests;

use App\Http\Requests\ApiBaseRequest;

class CustomerLoginRequest extends ApiBaseRequest
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
            'phone'     => 'required|regex:/(0)[0-9]{9}/',
        ];
    }
}