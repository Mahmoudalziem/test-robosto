<?php

namespace Webkul\Admin\Http\Requests\Inventory;

use App\Http\Requests\ApiBaseRequest;
use Webkul\Inventory\Models\InventoryProduct;
use Webkul\Inventory\Models\InventoryAdjustmentProduct;
use Webkul\Inventory\Models\InventoryWarehouse;
use Illuminate\Validation\Rule;
use Webkul\Core\Services\FixSKUs\FixSkus;
use App\Exceptions\ResponseErrorException;

class InventoryAdjustmentRequest extends ApiBaseRequest {

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
            'warehouse_id' => 'required|exists:warehouses,id',
            'products' => 'required',
            'products.*.product_id' => 'distinct',
        ];
        $skuIsEmpty = true;
        $isAreaManager = auth('admin')->user()->hasRole(['area-manager']) ?? true;

        if ($this->products) {
            foreach ($this->products as $k0 => $product) {

                // generate rules for each product
                // init product sku collection
                $fixSku = new FixSkus();
                $getProductSku = collect($fixSku->getProductSku($this->warehouse_id, $product['product_id']));
                $warehouseQty = $getProductSku['warehouseQty'];

                if ($product['skus']) {

                    // dd($rules, $product, $this->all());
                    foreach ($product['skus'] as $k1 => $sku) {

                        $rules['products.' . $k0 . '.skus.' . $k1 . '.sku'] = 'required|string';
                        $rules['products.' . $k0 . '.skus.' . $k1 . '.status'] = 'required_with:products.' . $k0 . '.skus.' . $k1 . '.qty|integer|between:1,5';
                        $statusRule = $rules['products.' . $k0 . '.skus.' . $k1 . '.status'];
                        // if warehouseQty == totalSkuQty get data from db
                        $invetorySku = collect($getProductSku['skuItems']);
                        $stock = $invetorySku->where('sku', $sku['sku'])->first();
                        $max_qty = $stock ? $stock['qty'] : 0;

                        if (isset($sku['status'])) {
                            if ($sku['status'] == InventoryAdjustmentProduct::STATUS_LOST || $sku['status'] == InventoryAdjustmentProduct::STATUS_EXPIRED || $sku['status'] == InventoryAdjustmentProduct::STATUS_DAMAGED || $sku['status'] == InventoryAdjustmentProduct::STATUS_RETURN_TO_VENDOR) { // decrease stock
                                // validate area manager with STATUS_RETURN_TO_VENDOR
                                if ($sku['status'] == InventoryAdjustmentProduct::STATUS_RETURN_TO_VENDOR && $isAreaManager == false) {
                                    //  throw new ResponseErrorException(406, ' Only Area Manager can make return to vendor ');
                                    $rules['products.' . $k0 . '.skus.' . $k1 . '.status'] = $statusRule . '|accepted';
                                }
                                $qtyRule = 'min:1|max:' . (int) $max_qty;
                            } else {
                                $qtyRule = 'min:' . (int) 1;
                            }
                            $rules['products.' . $k0 . '.skus.' . $k1 . '.qty'] = 'nullable|integer|' . $qtyRule;
                        } else {
                            $rules['products.' . $k0 . '.skus.' . $k1 . '.qty'] = 'nullable|integer';
                        }
                        // must be false if qty of sku added
                        if (isset($sku['qty']) && trim($sku['qty']) != "") {
                            $skuIsEmpty = false;
                        }
                    }

                    // get sum of sku qty from request per product where status is expired, damage and lost
                    $totalSkuQtyFromRequest = $this->totalSkuQtyFromRequest($product['skus']);
                    if (($totalSkuQtyFromRequest > $warehouseQty)) {
                        $rules['products.' . $k0 . '.skuQtyFromRequest'] = "nullable|integer|numeric|min:0|max:$warehouseQty";
                    }
                }
            }

            if ($skuIsEmpty)
                $rules['skus'] = 'required';
        }

        return $rules;
    }

    public function all($keys = null) {
        $data = parent::all();
        $data['skus'] = null;

        if ($data['products']) {
            foreach ($data['products'] as $k0 => $product) {
                $totalSkuQtyFromRequest = $this->totalSkuQtyFromRequest($product['skus']);
                $data['products'][$k0]['skuQtyFromRequest'] = $totalSkuQtyFromRequest;
            }
        }

        return $data;
    }

    public function messages() {
        return [
            "products.*.product_id.distinct" => 'This product is already taken!',
            "products.*.skus.*.status.accepted" => 'Only Area Managers can make return to vendor !'
        ];
    }

    public function totalSkuQtyFromRequest($productSkus) {
        $newProductCollection = [];
        foreach ($productSkus as $row) {
            if (!isset($row['status'])) {
                $row['status'] = null;
            }
            $row['qty'] = (int) ($row['qty'] ?? 0);
            $newProductCollection[] = ['sku' => $row['sku'], 'product_id' => $row['product_id'], 'qty' => $row['qty'], 'status' => $row['status']];
        }

        $productCollection = collect($newProductCollection);
        $totalSkuQtyFromRequest = $productCollection->whereIn('status', [InventoryAdjustmentProduct::STATUS_LOST, InventoryAdjustmentProduct::STATUS_EXPIRED, InventoryAdjustmentProduct::STATUS_DAMAGED])->sum('qty');
        return $totalSkuQtyFromRequest;
    }

}
