<?php

namespace Webkul\Driver\Http\Requests;

use App\Http\Requests\ApiBaseRequest;


class CompleteDeliverDriverWalletRequest extends ApiBaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {

        return  [
            'otp' => 'required|numeric',
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
