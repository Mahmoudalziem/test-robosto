<?php

namespace Webkul\Collector\Http\Resources\Inventory;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProdcutSKUsResource extends JsonResource
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
            'short_sku' =>  substr($this->sku, 0, 3),
            'qty' => (int)  number_format($this->qty, 0) ,
        ];

    }

}