<?php

namespace Webkul\Driver\Http\Resources;

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
                'image' => $this->item->image,
                'returnable' => $this->item->returnable,
                'quantity' => $this->qty_shipped,
                'price' => $this->price,
                'weight' => $this->weight,
                'unit_name' => $this->item->unit->name,
                'unit_value' => $this->item->unit_value,
        ];

    }

}