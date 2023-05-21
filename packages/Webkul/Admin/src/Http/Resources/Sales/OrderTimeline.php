<?php

namespace Webkul\Admin\Http\Resources\Sales;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderTimeline extends JsonResource
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
        ];

    }

}