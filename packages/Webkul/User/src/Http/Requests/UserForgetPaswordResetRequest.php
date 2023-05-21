<?php

namespace Webkul\User\Http\Requests;

use App\Http\Requests\ApiBaseRequest;

class UserForgetPaswordResetRequest extends ApiBaseRequest
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
            'otp' => 'required|exists:admins,otp',
            'email'      => 'email|required|exists:admins,email',
            'password'   => 'confirmed|min:6|required',
        ];
    }

    public function attributes()
    {
        return [
            'otp' => 'Pin Code',
        ];
    }
}