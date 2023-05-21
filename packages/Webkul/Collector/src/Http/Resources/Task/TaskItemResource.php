<?php

namespace Webkul\Collector\Http\Resources\Task;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskItemResource extends JsonResource
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
                'task_product_id' => $this->id,
                'product_name' => $this->product->name,
                'unit'=> $this->product->unit->name,
                'product_weight' => $this->product->weight,
                'barcode' => $this->product->barcode,                         
                'image' => $this->product->image_url,
                'qty' =>  (float)  number_format($this->qty, 2),
                'short_sku' =>  substr($this->sku, 0, 3),
                'sku' => $this->sku,
                'price'=> (float)  number_format($this->product->price, 2),
        ];

    }

}