<?php

namespace Webkul\Admin\Http\Requests\Inventory;

use App\Http\Requests\ApiBaseRequest;
use Webkul\Inventory\Models\InventoryProduct;

class InventoryTransactionRequest extends ApiBaseRequest
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
            'from_warehouse_id' => 'required|exists:warehouses,id',
            'to_warehouse_id' => 'required|exists:warehouses,id',
            'products' => 'required',
            'products.*.product_id' => 'distinct',            
        ];
        $skuIsEmpty = true;
        if ($this->products) {
            foreach ($this->products as $k0 => $product) {
                if ($product['skus']) {
                    foreach ($product['skus'] as $k1 => $sku) {
                        $rules['products.' . $k0 . '.skus.' . $k1 . '.sku'] = 'required|string';
                        $stock = InventoryProduct::where(['warehouse_id' => $this->from_warehouse_id, 'sku' => $sku['sku']])->first();
                        
                        // If No SKU ID provided, inject it manually
                        if (!isset($sku['inventory_product_id'])) {
                            $all = $this->all();
                            $all['products'][$k0]['skus'][$k1]['inventory_product_id'] = $stock->id;
                            $all['products'][$k0]['skus'][$k1]['product_id'] = $stock->product_id;
                            $this->replace($all);
                        }
                        $old_qty = $stock ? $stock->qty : 0;
                        if (isset($sku['qty']) && ($sku['qty'] != '' || $sku['qty'] != 0) ) {
                            $rules['products.' . $k0 . '.skus.' . $k1 . '.qty'] = 'required_with:products.' . $k0 . '.skus.' . $k1 . '.sku|integer|min:1|max:' . (int)$old_qty;
                            $skuIsEmpty = false;// must be false if qty of sku added
                        } else {
                            $rules['products.' . $k0 . '.skus.' . $k1 . '.qty'] = 'required|integer';
                        }
                    }
                }
            }
            if ($skuIsEmpty)
                $rules['skus'] = 'required';
        }

        return $rules;
    }

    public function all($keys = null)
    {
        $data = parent::all();
        $data['skus'] = null;
        return $data;
    }
    
    public function messages() {
        return [
            "products.*.product_id.distinct" => 'This product is already taken!'
        ];
    }      
}