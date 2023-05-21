<?php

namespace Webkul\Collector\Http\Resources\Inventory;


use Illuminate\Http\Request;


use Illuminate\Http\Resources\Json\JsonResource;
use Webkul\Inventory\Models\InventoryProduct;

class InventoryProductSingle extends JsonResource
{


    protected $append;
    public function __construct($resource,$append=null)
    {
        $this->append=$append;
        parent::__construct($resource);

    }


    public function toArray($request)
    {
        $items=InventoryProduct::where('qty','>',0)->where(['product_id'=>$this->product_id,'warehouse_id'=>auth()->user()->warehouse->id])->get();
            return [
                'product_id' => $this->product_id,
                'product_name' => $this->product->name,
                'qty' => (int)  number_format($this->qty, 0)  ,
                'unit'=> $this->product->unit->name,
                'product_weight' => $this->product->weight,
                'barcode' => $this->product->barcode,                
                'image' => $this->product->image_url,
                'thumb_url' => $this->product->thumb_url,
                'productSKUs'     => ProdcutSKUsResource::collection($items),
            ];


    }

}