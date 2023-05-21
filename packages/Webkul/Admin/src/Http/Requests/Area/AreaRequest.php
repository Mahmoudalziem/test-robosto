<?php

namespace Webkul\Admin\Http\Requests\Area;

use App\Http\Requests\ApiBaseRequest;

class AreaRequest extends ApiBaseRequest {

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
            'min_distance_between_orders' => 'required|numeric',
        ];
//        foreach (core()->getAllLocales() as $locale) {
//            $rules[$locale->code . '.' . 'name'] = 'required|unique:area_translations,name|string|min:2';
//        }

        // In Update
        if (isset($this->id)) {

            $rules['min_distance_between_orders'] = 'required|numeric';
//            foreach (core()->getAllLocales() as $locale) {
//                $rules[$locale->code . '.' . 'name'] = 'required|unique:area_translations,name,' . $this->id . ',area_id|string|min:2';
//            }
        }

        return $rules;
    }

}
