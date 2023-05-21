<?php

namespace Webkul\Customer\Http\Requests;

use Illuminate\Validation\Rule;
use App\Rules\CustomerEmailRule;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\ApiBaseRequest;

class CustomerRegisterRequest extends ApiBaseRequest
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
        Log::info(["data in rules" => $this->all()]);

        $phone = $this->phone;
        return [
            'phone'             => 'required|regex:/(0)[0-9]{9}/',
            'name'              => 'required|string|min:2|max:190',
            'email'            =>  ['nullable'], // , new CustomerEmailRule($phone)
            'avatar_id'         => 'required|numeric|exists:avatars,id',
            'referral_code'     => 'nullable|string',
        ];
    }
}