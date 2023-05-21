<?php

namespace Webkul\Driver\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Cache;
use Webkul\Product\Models\Product;

class OrderReturnItemResource extends JsonResource
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
                'product_id' => $this->product_id,
                'name' => $this->item->name,
                'image' => $this->item->image_url,
                'returnable' => $this->item->returnable,
                "qty"=> $this->qty_returned,
                 "return_reason"=> $this->return_reason ,
                'price' => $this->price,
        ];

    }

}