<?php

namespace Webkul\Admin\Http\Requests\Product;

use Illuminate\Validation\Rule;
use Webkul\Core\Rules\ImageBase64;
use App\Http\Requests\ApiBaseRequest;

class ProductRequest extends ApiBaseRequest
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
        $brand = $this->brand_id;
        
        $rules = [
            'barcode'           =>  'required|unique:products,barcode|string',
            'prefix'            =>  [
                'required',
                'max:2',
                'string',
                // Rule::unique('products', 'prefix')->where(function ($query) use($brand) {
                //         return $query->where('brand_id', $brand);
                // }),
            ],
            'image'             => 'required',
            'returnable'        =>  'required',
            'price'             =>  'required|numeric',
            'tax'               =>  'nullable|numeric',
            'note'              =>  'nullable',
            'weight'            =>  'required|numeric',
            'width'             =>  'required|numeric',
            'height'            =>  'required|numeric',
            'length'            =>  'required|numeric',
            'shelve_id'         =>  'required|numeric',
            'brand_id'          =>  'required|numeric',
            'unit_id'           =>  'required|numeric',
            'unit_value'        =>  'required',
            'sub_categories'    =>  'required|array|min:1',
        ];

        foreach (core()->getAllLocales() as $locale) {
          //  $rules[$locale->code. '.' . 'name']         =  'required|unique:product_translations,name,null,product_id,locale,'.$locale->code.'|string|min:2';
            $rules[$locale->code. '.' . 'name']         =  'required|string|min:2';
            $rules[$locale->code. '.' . 'description']  =  'required|string|min:2';
        }

        // In Update
        if (isset($this->id) ) {

            $rules['image']     = 'nullable';
            $rules['barcode']   = 'required|unique:products,barcode,'.$this->id.'|string';
            $rules['prefix']    = [
                'required',
                'max:2',
                'string',
                // Rule::unique('products', 'prefix')->ignore($this->id)->where(function ($query) use($brand) {
                //         return $query->where('brand_id', $brand);
                // }),
            ];
            

            foreach (core()->getAllLocales() as $locale) {
                //$rules[$locale->code. '.' . 'name']=  'required|unique:product_translations,name,'. $this->id.',product_id,locale,'.$locale->code.'|string|min:2';
                $rules[$locale->code. '.' . 'name']=  'required|string|min:2';
            }
        }

        return $rules;
    }
}