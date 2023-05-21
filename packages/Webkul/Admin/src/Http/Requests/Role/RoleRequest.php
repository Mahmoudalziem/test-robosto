<?php

namespace Webkul\Admin\Http\Requests\Role;

use App\Http\Requests\ApiBaseRequest;

class RoleRequest extends ApiBaseRequest {

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
            //'slug' => 'required',
            'permissions'                  =>  'required|array|min:1',
            'permissions.*'             =>  'required|numeric|exists:permissions,id',
           
        ];
        foreach (core()->getAllLocales() as $locale) {
            $rules[$locale->code . '.' . 'name'] = 'required|unique:role_translations,name|string|min:2';
        }
        // In Update
        if (isset($this->id)) {
            foreach (core()->getAllLocales() as $locale) {
                $rules[$locale->code . '.' . 'name'] = 'required|unique:role_translations,name,' . $this->id . ',role_id|string|min:2';
            }
        }
        return $rules;
    }

}
