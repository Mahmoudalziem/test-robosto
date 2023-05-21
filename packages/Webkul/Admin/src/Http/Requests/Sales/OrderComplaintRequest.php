<?php

namespace Webkul\Admin\Http\Requests\Sales;

use App\Http\Requests\ApiBaseRequest;

class OrderComplaintRequest extends ApiBaseRequest
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
            'text'                  =>  'required|string',
            'order_id'                =>  'required|numeric'
        ];
    }
}