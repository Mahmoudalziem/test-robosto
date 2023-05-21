<?php

namespace Webkul\Shipping\Http\Requests;

use App\Http\Requests\ApiBaseRequest;

class UpdateShippmentPriceRequest extends ApiBaseRequest
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
            'id' => 'required',
            'amount'=>'required|numeric'
        ];
    }
}