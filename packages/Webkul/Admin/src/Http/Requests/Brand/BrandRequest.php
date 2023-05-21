<?php

namespace Webkul\Admin\Http\Requests\Brand;

use App\Http\Requests\ApiBaseRequest;

class BrandRequest extends ApiBaseRequest
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
            'position'  =>  'nullable|numeric',
            'image'     =>  'required',
            'prefix'    =>  'required|max:2|unique:brands,prefix|string',
        ];

        foreach (core()->getAllLocales() as $locale) {
            $rules[$locale->code. '.' . 'name'] =  'required|unique:brand_translations,name|string|min:2';
        }

        // In Update
        if (isset($this->id) ) {

            $rules['prefix'] = 'required|max:2|unique:brands,prefix,'.$this->id.'|string';

            foreach (core()->getAllLocales() as $locale) {

                $rules[$locale->code. '.' . 'name'] =  'required|unique:brand_translations,name,'. $this->id.',brand_id|string|min:2';

            }
            $rules['image'] = 'nullable';
        }

        return $rules;
    }
}