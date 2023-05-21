<?php

namespace Webkul\Admin\Http\Requests\Accounting;

use App\Http\Requests\ApiBaseRequest;

class AreaManagerTransactionRequest extends ApiBaseRequest
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
            "area_id"           => 'required|numeric',
            "amount"            => 'required|numeric',
            "transaction_id"    => 'required',
            "transaction_date"  => 'required',
            "image"             => 'required',
            "note"              => 'nullable|string',
        ];
        return $rules;
    }
}
