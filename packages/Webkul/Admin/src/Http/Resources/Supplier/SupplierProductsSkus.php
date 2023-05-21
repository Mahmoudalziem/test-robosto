<?php

namespace Webkul\Admin\Http\Resources\Supplier;

use App\Http\Resources\CustomResourceCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class SupplierProductsSkus extends CustomResourceCollection
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request
     * @return Collection
     */
    public function toArray($request)
    {
        return  $this->collection->map(function ($purchaseOrder) {
            return [
                'id'            => $purchaseOrder->id,
                'purchase_order_id'         => $purchaseOrder->purchase_order_id,
                'purchase_order_no'         => $purchaseOrder->purchaseOrder->purchase_order_no,
                'invoice_no'         => $purchaseOrder->purchaseOrder->invoice_no,
                'sku'         => $purchaseOrder->sku,
                'cost'         => $purchaseOrder->cost
            ];
        });
    }
}
