<?php

namespace Webkul\Product\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class Product extends JsonResource {

    protected $append;
    protected $model;

    public function __construct($resource, $append = null) {
        $this->append = $append;
        $this->model = $resource;
        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @param  Request
     * @return array
     */
    public function toArray($request) {
        $productLabel = isset($this->label) ? $this->label->name : null;
        $validDate = false;
        $discountType = null;
        $discountValue = null;
        $finalPrice = $this->price;
        $area = $request->header('area');
        $now = \Carbon\Carbon::now()->toDateTimeString();
        $discountDetails = $this->discount_details ?? null;

        // force label to display discount label
        if ($discountDetails) {
            $discountArea = $discountDetails[$area] ?? null;

            if (isset($discountArea) && isset($discountArea['start_validity']) && isset($discountArea['end_validity'])) {
                $validDate = ($now >= $discountArea['start_validity']  && $now <= $discountArea['end_validity'] ) || $discountArea['discount_qty'] > 0 ? true : false;
                $discountType = $discountArea['discount_type'];
                $discountValue = $discountArea['discount_value'];
                if ($discountArea['discount_type'] == 'val') {
                    $productLabel = $discountArea['discount_value'] . ' ' . __('core::app.currency_egp') . ' ' . __('core::app.off');
                }

                if ($discountArea['discount_type'] == 'per') {
                    $productLabel = $discountArea['discount_value'] . ' % ' . __('core::app.off');
                }
                $finalPrice = isset($discountArea['discount_price']) && $validDate ? $discountArea['discount_price'] : $this->price;
            }


            if ($validDate == false) {

                $this->model->discount_details = null;
                $this->model->productlabel_id = null;
                $this->model->save();
                $productLabel = null;
            }
        }

        return [
            'id' => $this->id,
            'image_url' => $this->image_url,
            'thumb_url' => $this->thumb_url,
            'price' => $this->price,
            'valid_discount' => $validDate,
            'discount_type' => $discountType,
            'discount_value' => $discountValue,
            'product_label' => $productLabel,
            //'discountDetails'=>$discountDetails,
            'final_price' => $finalPrice,
            'tax' => $this->tax,
            'weight' => $this->weight,
            'width' => $this->width,
            'height' => $this->height,
            'length' => $this->length,
            'unit_name' => $this->unit->name,
            'unit_value' => $this->unit_value,
            'name' => $this->name,
            'description' => $this->description,
            'total_in_stock' => $this->total_in_stock,
            'related_products' => new ProductAll($this->relatedProducts()),
        ];
    }

}
