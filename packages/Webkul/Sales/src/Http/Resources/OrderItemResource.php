<?php

namespace Webkul\Sales\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    protected $append;
    public function __construct($resource,$append=null)
    {
        $this->append=$append;
        parent::__construct($resource);

    }

    /**
     * Transform the resource into an array.
     *
     * @param  Request
     * @return array
     */
    public function toArray($request)
    {
        return [
                'id' => $this->id,
                'name' => $this->item->name,
                'product_name' => $this->item->name,
                'image' => $this->item->image,
                'returnable' => $this->item->returnable,
                'quantity' => $this->qty_shipped,
                'qty' => $this->qty_shipped,
                'price' => $this->price,
                'weight' => $this->weight,
                'discount_amount' => $this->discount_amount,
                'discount_type' => $this->discount_type,
        ];

    }

}