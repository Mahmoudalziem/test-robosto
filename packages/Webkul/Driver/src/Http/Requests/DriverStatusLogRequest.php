<?php

namespace Webkul\Driver\Http\Requests;

use App\Http\Requests\ApiBaseRequest;


class DriverStatusLogRequest extends ApiBaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {

        return  [
            'order_id' => 'nullable|numeric',
            'type'  => 'nullable|string|in:online,offline,break,emergency',
            'duration'  => 'nullable|numeric',
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }
}
