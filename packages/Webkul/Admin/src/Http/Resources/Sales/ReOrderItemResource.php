<?php

namespace Webkul\Admin\Http\Resources\Sales;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Webkul\Admin\Http\Resources\Sales\OrderItemSkuResource;
use Webkul\Sales\Models\Order as OrderModel;

class ReOrderItemResource extends JsonResource {

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
        $product = $this->item;
        $margin = $this->order->paid_type == OrderModel::PAID_TYPE_BNPL ? config('robosto.BNPL_INTEREST') : 0;
        $product->price = $product->price + ($product->price * $margin );
        return [
            'id'                    => $product->id,
            'image_url'             => $product->image_url,
            'thumb_url'             => $product->thumb_url,
            'price'                 => $product->price,
            'unit_name'             => $product->unit->name,
            'unit_value'            => $product->unit_value,
            'name'                  => $product->name,
            'qty'                   => $this->qty_shipped,
            'total_in_stock'        => $this->getTotalInStock($product)
        ];
    }

    /**
     * @param mixed $item
     * 
     * @return mixed
     */
    private function getTotalInStock($product)
    {
        $status = request()->header('status') ?? 'update';
        
        if ($status == 'update') {
            return $product->total_in_stock + $this->qty_shipped;
        }
        
        return $product->total_in_stock;        
    }

}
