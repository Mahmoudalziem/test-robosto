<?php

namespace Webkul\Collector\Http\Resources\Inventory;


use Illuminate\Http\Request;


use Illuminate\Http\Resources\Json\JsonResource;
use Webkul\Inventory\Models\InventoryProduct;

class ProductSingle extends JsonResource
{


    protected $append;
    public function __construct($resource,$append=null)
    {
        $this->append=$append;
        parent::__construct($resource);

    }


    public function toArray($request)
    {
    
            return [
                'product_id' => $this->id,
                'product_name' => $this->name,
                'unit'=> $this->unit->name,
                'product_weight' => $this->weight,
                'barcode' => $this->barcode,                
                'image' => $this->image_url,
                'thumb_url' => $this->thumb_url,
               
            ];


    }

}