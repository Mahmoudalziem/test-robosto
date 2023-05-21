<?php


namespace Webkul\Admin\Http\Resources\PurchaseOrder;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Webkul\Admin\Http\Resources\Area\Area;
use App\Http\Resources\CustomResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderProducts extends CustomResourceCollection
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request
     * @return Collection
     */
    public function toArray($request)
    {
        return  $this->collection->map(function ($product) {
            $originalProdcut=$product->product()->first();
    
            return [
                'id' => $product->product->id,
                'barcode' => $originalProdcut->barcode,
                'unit' => $originalProdcut->unit?$originalProdcut->unit->name:null,
                'unit_value' => $originalProdcut->unit_value,                
                'name' => $product->product->name,
                'image' => $product->product()->first()->image,
                'image_url' => $product->product()->first()->image_url,
                'thumb_url' => $product->product()->first()->thumb_url,
                'product_price' => $originalProdcut->price,
                'total_in_stock' => $product->product->total_in_stock,
                'sku' => $product->sku,
                'qty' => $product->qty,
                'cost_before_discount' => $product->cost_before_discount,
                'cost' => $product->cost,
                'amount_before_discount' => $product->amount_before_discount,
                'amount' => $product->amount,
                'prod_date' => $product->prod_date,
                'exp_date' => $product->exp_date,
            ];
        });
    }

}