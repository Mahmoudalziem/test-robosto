<?php

namespace Webkul\Admin\Http\Requests\Driver;

use App\Http\Requests\ApiBaseRequest;

class DriverRequest extends ApiBaseRequest
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
            'area_id'  =>  'required|exists:areas,id',
            'warehouse_id'  =>  'required|exists:warehouses,id',
            'name'  =>  'required',
            'email'  =>  'required|email|unique:drivers,email',
            'username'  =>  'required|unique:drivers,username',
            'id_number'  =>  'nullable|unique:drivers,id_number',
            'password'  =>  'required',
            'address'  =>  'required',
            'phone_work' => ['required','regex:/^(01)(0|1|2|5)[0-9]{8}$/'], // this is mobile regex
            'phone_private' => ['required','regex:/^(01)(0|1|2|5)[0-9]{8}$/'], // this is mobile regex
            'image'     =>  'required',
            'image_id'     =>  'required',
        ];

        // In Update
        if (isset($this->driver->id) ) {
            $rules['email'] = 'required|unique:drivers,email,'.$this->driver->id;
            $rules['username'] = 'required|unique:drivers,username,'.$this->driver->id;
            $rules['id_number'] = 'nullable|unique:drivers,id_number,'.$this->driver->id;
            $rules['password'] = 'nullable';
            $rules['image'] = 'nullable';
            $rules['image_id'] = 'nullable';
        }

        return $rules;
    }
}