<?php

namespace Webkul\Admin\Http\Requests\Supplier;

use App\Http\Requests\ApiBaseRequest;

class SupplierRequest extends ApiBaseRequest
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

        $rules = [
            'name'                      =>  'required|string|min:2|max:200',
            'email'                     =>  'required|email',
            'work_phone'                =>  'nullable|string',
            'mobile_phone'              =>  'nullable|string',
            'company_name'              =>  'required|string',
            'address_title'             =>  'required|string',
            'address_city'              =>  'required|string',
            'address_state'             =>  'required|string',
            'address_zip'               =>  'required|numeric',
            'address_phone'             =>  'required|string',
            'address_fax'               =>  'nullable|numeric',
            'remarks'                   =>  'nullable|string',
            'areas'                     =>  'required|array|min:1',
            'products'                  =>  'required|array|min:1',
            'products.*.product_id'     =>  'required|numeric|exists:products,id',
            'products.*.brand_id'       =>  'required|numeric|exists:brands,id',
        ];

        return $rules;
    }
}