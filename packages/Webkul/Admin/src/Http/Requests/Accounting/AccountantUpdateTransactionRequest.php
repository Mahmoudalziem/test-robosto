<?php

namespace Webkul\Admin\Http\Requests\Accounting;

use App\Http\Requests\ApiBaseRequest;

class AccountantUpdateTransactionRequest extends ApiBaseRequest
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
            "status"    => 'required|in:received,rejected',
            "note"      => 'nullable|string',
        ];
    }
}
