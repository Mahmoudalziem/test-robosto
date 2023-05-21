<?php

namespace Webkul\Admin\Http\Requests\PurchaseOrder;

use App\Http\Requests\ApiBaseRequest;

class PurchaseOrderRequest extends ApiBaseRequest
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
            'is_draft'                  =>  'required|numeric',
            'discount_type'             =>  'nullable|string',
            'discount'                  =>  'nullable|numeric',
            'supplier_id'               =>  'required|numeric|exists:suppliers,id',
            'area_id'                   =>  'required|numeric',
            'warehouse_id'              =>  'required|numeric',
            'products'                  =>  'required|array|min:1',
            'products.*.id'             =>  'required|distinct|numeric|exists:products,id',
            'products.*.qty'            =>  'required|numeric|min:1',
            'products.*.cost'           =>  'required|numeric|gt:0',
            'products.*.prod_date'      =>  'required|date',
            'products.*.exp_date'       =>  'required|date',
        ];
    }
    
    public function messages() {
        return [
            "products.*.id.distinct" => 'This product is already taken!'
        ];
    }      
}