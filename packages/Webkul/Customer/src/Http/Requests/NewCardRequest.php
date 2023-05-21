<?php

namespace Webkul\Customer\Http\Requests;

use App\Http\Requests\ApiBaseRequest;
use App\Rules\Creditcard;

class NewCardRequest extends ApiBaseRequest
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
            'card_number'          => ['required', 'numeric', 'regex:/^[0-9]{16}$/', new Creditcard],
            'card_exp'             => 'required|numeric|regex:/^[0-9]{4}$/',
            'card_cvc'             => 'required|numeric|regex:/^[0-9]{3}$/',
            'card_name'            => 'required|string',
        ];
    }
}