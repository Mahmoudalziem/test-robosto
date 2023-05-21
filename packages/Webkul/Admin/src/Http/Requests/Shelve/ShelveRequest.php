<?php

namespace Webkul\Admin\Http\Requests\Shelve;

use App\Http\Requests\ApiBaseRequest;
use Webkul\Core\Rules\ImageBase64;

class ShelveRequest extends ApiBaseRequest
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
            'name' => 'required|string|unique:shelves',
            'position' => 'required|numeric|min:1|unique:shelves',
            'row' => 'required|numeric|min:1',
        ];

        // In Update
        if (isset($this->id)) 
        {
            $rules['name'] = 'required|string|unique:shelves,name,'.$this->id;
            $rules['position'] = 'required|numeric|unique:shelves,position,'.$this->id;
        }


        return $rules;
    }
}