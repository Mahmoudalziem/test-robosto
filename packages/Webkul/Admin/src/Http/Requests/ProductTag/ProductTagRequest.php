<?php

namespace Webkul\Admin\Http\Requests\ProductTag;

use Illuminate\Validation\Rule;
use Webkul\Core\Rules\ImageBase64;
use App\Http\Requests\ApiBaseRequest;

class ProductTagRequest extends ApiBaseRequest {

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize() {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules() {


        $rules = [
        ];

        foreach (core()->getAllLocales() as $locale) {
            $rules[$locale->code . '.' . 'name'] = 'required|unique:product_tag_translations,name|string|min:2';
        }
        
        // In Update
        if (isset($this->id)) {
            foreach (core()->getAllLocales() as $locale) {
                $rules[$locale->code . '.' . 'name'] = 'required|unique:product_tag_translations,name,' . $this->id . ',product_tag_id|string|min:2';
            }
        }
 
        return $rules;
    }

}
