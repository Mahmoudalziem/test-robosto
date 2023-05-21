<?php

namespace Webkul\Admin\Http\Requests\Sales;

use App\Http\Requests\ApiBaseRequest;

class OrderViolationRequest extends ApiBaseRequest
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
            'type'                  =>  'required|string|in:driver,collector',
            'violation_type'        =>  'required|string',
            'violation_note'        =>  'required|string'
        ];
    }
}