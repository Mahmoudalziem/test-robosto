<?php

namespace Webkul\Admin\Http\Resources\Sales;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Webkul\Admin\Http\Resources\Sales\OrderItemSkuResource;

class OrderItemResource extends JsonResource {

    protected $append;

    public function __construct($resource, $append = null) {
        $this->append = $append;
        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @param  Request
     * @return array
     */
    public function toArray($request) {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'name' => $this->item->name,
            'image_url' => $this->item->image_url,
            'returnable' => $this->item->returnable,
            'quantity' => $this->qty_shipped,
            'price' => $this->price,
            'total' => $this->total,
            'base_total' => $this->base_total,
            'discount_amount' => $this->discount_amount,
            'discount_type' => $this->discount_type,
            'unit_name' => $this->item->unit->name,
            'unit_value' => $this->item->unit_value,
            'description' => $this->item->description,
            'position' => $this->shelve_position,
            'shelf' => $this->shelve_name,
            'skus' =>   OrderItemSkuResource::collection($this->skus->sortByDesc('qty')),
        ];
    }

}
