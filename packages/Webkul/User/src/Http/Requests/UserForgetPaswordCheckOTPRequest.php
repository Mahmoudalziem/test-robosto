<?php

namespace Webkul\User\Http\Requests;

use App\Http\Requests\ApiBaseRequest;
class UserForgetPaswordCheckOTPRequest extends ApiBaseRequest
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
        return  [

           'email' => 'required|email|exists:admins,email',
           'otp' => 'required|exists:admins,otp',
            //'email' => 'required|email|exists:admins,email,otp,' . $this->otp,
            // 'otp'  => 'required|exists:admins,otp|email|exists:admins,email,otp,' . $this->otp,
        ];
    }

    public function attributes()
    {
        return [
            'otp' => 'Pin Code',
        ];
    }
}