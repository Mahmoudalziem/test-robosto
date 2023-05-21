<?php

namespace Webkul\Admin\Http\Requests\Bundle;

use Webkul\Product\Models\Product;
use App\Http\Requests\ApiBaseRequest;
use Webkul\Bundle\Models\Bundle;

class BundleRequest extends ApiBaseRequest {

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
            'image' => 'required',
            'discount_type' => 'required|in:val,per',
            'amount' => 'nullable|numeric',
            'start_validity' => 'nullable|date',
            'end_validity' => 'nullable|date',
            'areas' => 'required|array|min:1',
            'areas.*' => 'exists:areas,id', // check each item in the array
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|numeric|exists:products,id',
            'items.*.qty' => 'required|numeric',
        ];

        if ($this->discount_type == Bundle::DISCOUNT_TYPE_PERCENT) {
            $rules['discount_value'] = 'required|numeric|max:100';
        } else {
            $totalProductsPrice = $this->calculateTotalProductsPrice((array) $this->items);
            $rules['discount_value'] = 'required|numeric|max:' . $totalProductsPrice;
        }

        foreach (core()->getAllLocales() as $locale) {
            $rules[$locale->code . '.' . 'name'] = 'required|string|min:2';
            $rules[$locale->code . '.' . 'description'] = 'required|string|min:2';
        }

        // In Update
        if (isset($this->id)) {
            $rules['image'] = 'nullable';
            //$rules['area_id']     = 'required';
            $rules['areas'] = 'required';
            $rules['areas.*'] = 'exists:areas,id'; // check each item in the array

            foreach (core()->getAllLocales() as $locale) {
                $rules[$locale->code . '.' . 'name'] = 'required|string|min:2';
            }
        }

        return $rules;
    }

    /**
     * @param array $items
     * @return mixed
     */
    protected function calculateTotalProductsPrice(array $items) {
        $itemsIDs = array_column($items, 'id');
        $productsFromDB = Product::whereIn('id', $itemsIDs)->get();

        $totalPrice = 0;
        foreach ($items as $item) {

            $product = $productsFromDB->find($item['id']);

            $totalPrice += $item['qty'] * $product->price;
        }

        return $totalPrice;
    }

}
