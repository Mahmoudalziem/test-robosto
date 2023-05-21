<?php

namespace Webkul\Admin\Http\Requests\Banner;

use App\Http\Requests\ApiBaseRequest;
use Webkul\Core\Rules\ImageBase64;

class BannerAddRequest extends ApiBaseRequest
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
    public function rules() {

        $rules = [
            'area_id' => ['required', 'array', 'exists:areas,id'], //'required|exists:areas,id',
            'name' => 'required',
            'position' => 'required',
            'actionable_type' => 'nullable',
            'section' => 'required',
        ];

        if ($this->actionable_type == 'Category' || $this->actionable_type == 'SubCategory' || $this->actionable_type == 'Product') {
            $rules['action_id'] = 'required|integer|gt:0';
        } else {
            $rules['action_id'] = 'nullable';
        }

        // In Update
        if (isset($this->banner)) {

            $rules = [
                'area_id' => 'required|exists:areas,id',
                'name' => 'required',
                'position' => 'required',
                'actionable_type' => 'nullable',
                'section' => 'required',
            ];

            if ($this->actionable_type == 'Category' || $this->actionable_type == 'SubCategory' || $this->actionable_type == 'Product') {
                $rules['action_id'] = 'required|integer|gt:0';
            } else {
                $rules['action_id'] = 'nullable';
            }
            
            foreach (core()->getAllLocales() as $locale) {
                $rules['image_' . $locale->code] = ['nullable'];
            }
        }

        return $rules;
    }
}
