<?php

namespace Webkul\Collector\Http\Requests;

use App\Http\Requests\ApiBaseRequest;


class CollectorOrderReturnedRequest extends ApiBaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {

        return  [
            'order_id'          => 'required|numeric',
            'items'             => 'required|array',
            'items.*.id'        => 'required|numeric',
            'items.*.qty'       => 'required|numeric',
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
