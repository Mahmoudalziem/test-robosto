<?php

namespace Webkul\Collector\Http\Requests;

use App\Http\Requests\ApiBaseRequest;


class ItemStockRequest extends ApiBaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {

        return  [
            'product_id' => 'required|numeric',
            'qty_stock' => 'required'
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
