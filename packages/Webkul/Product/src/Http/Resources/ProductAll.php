<?php

namespace Webkul\Product\Http\Resources;

use App\Http\Resources\CustomResourceCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Webkul\Inventory\Models\InventoryArea;
use Illuminate\Support\Facades\Log;

class ProductAll extends CustomResourceCollection {

    /**
     * Transform the resource into an array.
     *
     * @param  Request
     * @return Collection
     */
    public function toArray($request) {

        $area = $request->header('area');
        $now = \Carbon\Carbon::now()->toDateTimeString();


        $allData = $this->collection->filter(function ($product) {
                    return $product->total_in_stock > 0;
                })->map(function ($product) use ($area, $now) {
            $productLabel = isset($product->label) ? $product->label->name : null;
            $validDate = false;
            $discountType = null;
            $discountValue = null;
            $finalPrice = $product->price;

            $discountDetails = $product->discount_details ?? null;

            // force label to display discount label
            if ($discountDetails) {
                $discountArea = $discountDetails[$area] ?? null;

                if (isset($discountArea) && isset($discountArea['start_validity']) && isset($discountArea['end_validity'])) {
                    $validDate = ($now >= $discountArea['start_validity'] && $now <= $discountArea['end_validity'] ) || $discountArea['discount_qty'] > 0 ? true : false;
                    $discountType = $discountArea['discount_type'];
                    $discountValue = $discountArea['discount_value'];
                    if ($discountArea['discount_type'] == 'val') {
                        $productLabel = $discountArea['discount_value'] . ' ' . __('core::app.currency_egp') . ' ' . __('core::app.off');
                    }

                    if ($discountArea['discount_type'] == 'per') {
                        $productLabel = $discountArea['discount_value'] . ' % ' . __('core::app.off');
                    }
                    $finalPrice = isset($discountArea['discount_price']) && $validDate ? $discountArea['discount_price'] : $product->price;
                }

                if ($validDate == false) {

                    $product->discount_details = null;
                    $product->productlabel_id = null;
                    $product->save();
                    $productLabel = null;
                }
            }

            $data = [
                'id' => $product->id,
                'bundle_id' => $product->bundle_id,
                'image_url' => $product->image_url,
                'thumb_url' => $product->thumb_url,
                'price' => $product->price,
                'valid_discount' => $validDate,
                'discount_type' => $discountType,
                'discount_value' => $discountValue,
                'product_label' => $productLabel,
                'final_price' => $finalPrice,
                'barcode' => $product->barcode,
                'tax' => $product->tax,
                'weight' => $product->weight,
                'width' => $product->width,
                'height' => $product->height,
                'length' => $product->length,
                'unit_name' => $product->unit->name,
                'unit_value' => $product->unit_value,
                'name' => $product->name,
                'description' => $product->description,
                'total_in_stock' => $product->total_in_stock,
            ];
            return $data;
        });
 
           
     
        return $allData;
    }

}
