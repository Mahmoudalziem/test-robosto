<?php

namespace Webkul\Admin\Http\Requests\Category;

use App\Http\Requests\ApiBaseRequest;

class SubCategoryRequest extends ApiBaseRequest
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
            'position'      =>  'nullable|numeric',
            'image'         =>  'required',
            'categories'    =>  'nullable|array|min:1'
        ];

        foreach (core()->getAllLocales() as $locale) {
            $rules[$locale->code. '.' . 'name'] =  'required|unique:sub_category_translations,name,null,sub_category_id,locale,'.$locale->code.'|string|min:2';
        }

        // In Update
        if (isset($this->id) ) {
            $rules['image'] = 'nullable';
            foreach (core()->getAllLocales() as $locale) {
                $rules[$locale->code. '.' . 'name']=  'required|unique:sub_category_translations,name,'. $this->id.',sub_category_id,locale,'.$locale->code.'|string|min:2';
            }
        }

        return $rules;
    }
}