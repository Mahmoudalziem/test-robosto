<?php

namespace Webkul\Admin\Http\Requests\SmsCampaign;

use App\Http\Requests\ApiBaseRequest;

class SmsCampaignRequest extends ApiBaseRequest {

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
            "content" => 'required|string',
            'tags' => 'nullable|array|min:1' ,
            'customers' => 'nullable|array|min:1' ,
        ];


        return $rules;
    }

}
