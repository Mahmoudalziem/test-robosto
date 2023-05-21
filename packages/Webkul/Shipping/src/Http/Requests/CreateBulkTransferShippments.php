<?php

namespace Webkul\Shipping\Http\Requests;

use App\Http\Requests\ApiBaseRequest;

class CreateBulkTransferShippments extends ApiBaseRequest
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
            'shipments' => 'required|array',
            'shipments.*'=>'exists:shippments,id',
            'from_warehouse' => 'required',
            'to_warehouse'=>'required'
        ];
    }
}