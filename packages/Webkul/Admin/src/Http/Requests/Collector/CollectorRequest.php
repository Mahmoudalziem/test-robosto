<?php

namespace Webkul\Admin\Http\Requests\Collector;

use App\Http\Requests\ApiBaseRequest;

class CollectorRequest extends ApiBaseRequest
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
            'email'  =>  'required|email|unique:collectors,email',
            'username'  =>  'required|unique:collectors,username',
            'id_number'  =>  'nullable|unique:collectors,id_number',
            'password'  =>  'required',
            'address'  =>  'required',
            'phone_work' => ['required','regex:/^(01)(0|1|2|5)[0-9]{8}$/'], // this is mobile regex
            'phone_private' => ['required','regex:/^(01)(0|1|2|5)[0-9]{8}$/'], // this is mobile regex
             'image'     =>  'required',
            'image_id'     =>  'required',
        ];

        // In Update
        if (isset($this->collector->id) ) {
            $rules['email'] = 'required|email|unique:collectors,email,'.$this->collector->id;
            $rules['username'] = 'required|unique:collectors,username,'.$this->collector->id;
            $rules['id_number'] = 'nullable|unique:collectors,id_number,'.$this->collector->id;
            $rules['password'] = 'nullable';
            $rules['image'] = 'nullable';
            $rules['image_id'] = 'nullable';
        }

        return $rules;
    }
}