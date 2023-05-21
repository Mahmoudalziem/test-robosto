<?php

namespace Webkul\Admin\Http\Requests\User;

use App\Http\Requests\ApiBaseRequest;

class UserRequest extends ApiBaseRequest {

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
            'areas' => 'required',
            'roles' => 'required',
            'name' => 'required',
            'email' => 'required|email|unique:admins,email',
            'username' => 'required|unique:admins,username',
            'id_number' => 'nullable|unique:admins,id_number',
            'password' => 'required',
            'address' => 'required',
            'phone_work' => ['required', 'regex:/^(01)(0|1|2|5)[0-9]{8}$/'], // this is mobile regex
            'phone_private' => ['required', 'regex:/^(01)(0|1|2|5)[0-9]{8}$/'], // this is mobile regex
            'image' => 'required',
        ];

        // In Update

        if (isset($this->user->id)) {
            $rules['email'] = 'required|unique:admins,email,' . $this->user->id;
            $rules['username'] = 'required|unique:admins,username,' . $this->user->id;
            $rules['id_number'] = 'nullable|unique:admins,id_number,' . $this->user->id;
            $rules['password'] = 'nullable';
            $rules['image'] = 'nullable';
        }

        return $rules;
    }

}
