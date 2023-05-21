<?php

namespace Webkul\Customer\Http\Requests;

use App\Http\Requests\ApiBaseRequest;

class CustomerFavoriteRequest extends ApiBaseRequest
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
            'product_id'             => 'required|numeric',
            'favorite'               => 'required|in:0,1',
        ];
    }
}