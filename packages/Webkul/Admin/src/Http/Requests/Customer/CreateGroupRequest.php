<?php

namespace Webkul\Admin\Http\Requests\Customer;

use App\Http\Requests\ApiBaseRequest;


class CreateGroupRequest extends ApiBaseRequest
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
            'name'      => 'required|unique:tags,name|min:2|max:25',
            'customers' => 'nullable|array|min:1|exists:customers,id',
            'tags'      => 'nullable|array|min:1|exists:tags,id'
        ];
    }
}