<?php

namespace Webkul\Admin\Http\Resources\PurchaseOrder;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use App\Http\Resources\CustomResourceCollection;

class PurchaseOrder extends CustomResourceCollection {

    /**
     * Transform the resource into an array.
     *
     * @param  Request
     * @return Collection
     */
    public function toArray($request) {
        return $this->collection->map(function ($purchaseOrder) {

                    return [
                'id' => $purchaseOrder->id,
                'created_by' => $purchaseOrder->createdBy ? $purchaseOrder->createdBy->name : null,
                'date' => Carbon::parse($purchaseOrder->updated_at)->format('d M Y H:i a'),
                'purchase_order_no' => $purchaseOrder->purchase_order_no,
                'invoice_no' => $purchaseOrder->invoice_no,
                'supplier' => $purchaseOrder->supplier->name,
                'status' => $purchaseOrder->is_draft,
                'amount' => $purchaseOrder->total_cost,
                'warehouse' => $purchaseOrder->warehouse->name,
                    ];
                });
    }

}
