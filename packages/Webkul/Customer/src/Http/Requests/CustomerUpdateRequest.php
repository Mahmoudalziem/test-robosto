<?php

namespace Webkul\Customer\Http\Requests;

use App\Http\Requests\ApiBaseRequest;

class CustomerUpdateRequest extends ApiBaseRequest
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

        return [
            'email'             => 'nullable|email|unique:customers,email,'.$this->id,
            'name'              => 'required|string|min:2|max:190',
            'avatar_id'         => 'required|numeric|exists:avatars,id',
        ];
    }

    public function all($keys = null)
    {
        $data = parent::all();
        $data['id'] =  auth('customer')->user()->id;
        return $data;
    }
}