<?php

namespace Webkul\Admin\Http\Resources\PurchaseOrder;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Webkul\Admin\Http\Resources\Area\Area;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderSingle extends JsonResource {

    protected $append;

    public function __construct($resource, $append = null) {
        $this->append = $append;
        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request) {
        return [
            'id' => $this->id,
            'created_by' => $this->createdBy ? $this->createdBy->name : null,
            'date' => Carbon::parse($this->updated_at)->format('d M Y H:i a'),
            'purchase_order_no' => $this->purchase_order_no,
            'invoice_no' => $this->invoice_no,
            'supplier' => $this->supplier,
            'warehouse' => new PurchaseOrderWarehouse($this->warehouse()->first()),
            'is_draft' => $this->is_draft,
            'total_cost' => $this->total_cost,
            'sub_total_cost' => $this->sub_total_cost,
            'discount_type' => $this->discount_type,
            'discount' => $this->discount,
            'products' => new PurchaseOrderProducts($this->products),
        ];
    }

}
