<?php

namespace Webkul\Admin\Http\Requests\Notification;

use App\Http\Requests\ApiBaseRequest;
use Webkul\Core\Rules\ImageBase64;

class NotificationRequest extends ApiBaseRequest
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
            'title' => 'required|string|max:50',
            'body' => 'required|string|max:1000',
            'scheduled_at' => 'nullable',
            'tags' => 'required|array',
        ];

        return $rules;
    }
}