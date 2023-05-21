<?php

namespace Webkul\Admin\Http\Requests\Productlabel;

use Illuminate\Validation\Rule;
use Webkul\Core\Rules\ImageBase64;
use App\Http\Requests\ApiBaseRequest;

class ProductlabelRequest extends ApiBaseRequest {

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
            'slug'=> 'required|unique:productlabels,slug|string',
        ];

        foreach (core()->getAllLocales() as $locale) {
            $rules[$locale->code . '.' . 'name'] = 'required|unique:productlabel_translations,name|string|min:2';
        }
        
        // In Update
        if (isset($this->id)) {
            foreach (core()->getAllLocales() as $locale) {
                 $rules[ 'slug'] ='required|unique:productlabels,slug,' . $this->id . ',id|string';
                $rules[$locale->code . '.' . 'name'] = 'required|unique:productlabel_translations,name,' . $this->id . ',productlabel_id|string|min:2';
            }
        }
 
        return $rules;
    }

}
