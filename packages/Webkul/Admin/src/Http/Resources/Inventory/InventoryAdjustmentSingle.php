<?php

namespace Webkul\Admin\Http\Resources\Inventory;

use Illuminate\Http\Resources\Json\JsonResource;

class InventoryAdjustmentSingle extends JsonResource {

    protected $append;

    public function __construct($resource, $append = null) {
        $this->append = $append;
        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request) {

        if ($this->status == 0) {
            $status = 'Canceled';
        } elseif ($this->status == 1) {
            $status = 'Pending';
        } elseif ($this->status == 2) {
            $status = 'Approved';
        }

        $totalCost = 0;
        $prodcutList = [];

        foreach ($this->adjustmentProducts as $prod) {
            // '1 => Lost, 2 => Expired, 3 => Over Qty, 3 => Damaged'
            if ($prod->status == 1) {
                $productStatus = 'Lost';
            } elseif ($prod->status == 2) {
                $productStatus = 'Expired';
            } elseif ($prod->status == 3) {
                $productStatus = 'Over Qty';
            } elseif ($prod->status == 4) {
                $productStatus = 'Damaged';
            }elseif ($prod->status == 5) {
                $productStatus = 'ٌReturn To Vendor';
            }
            
            $productTable = $prod->product;
            $productObj['id'] = $prod->id;
            $productObj['unit'] = $productTable->unit;
            $productObj['warehouse_id'] = $this->warehouse->id;
            $productObj['inventory_adjustment_product_id'] = $prod->id;
            $productObj['product_id'] = $productTable->id;
            $productObj['productObj'] = $productTable;
            $productObj['productName'] = $productTable->name;
            $productObj['sku'] = $prod->sku;
            $productObj['qty'] = $prod->qty;

            $productObj['cost'] = $prod->purchaseOrder->cost ?? 0;
            $productObj['status'] = $prod->status;
            $productObj['statusName'] = $productStatus;
            $productObj['note'] = $prod->note;
            $productObj['inventory_adjustment_id'] = $prod->inventory_adjustment_id;

            $totalCost += $prod->qty * $productObj['cost'];
            array_push($prodcutList, $productObj);
        }

        return [
            'id' => $this->id,
            'warehouse_id' => $this->warehouse->id,
            'warehouse' => $this->warehouse->name,
            'Area' => $this->warehouse->area->name,
            'total_cost' => $totalCost,
            'status' => $this->status,
            'statusName' => $status,
            'products' => $prodcutList,
            'timeline' => $this->handleTimeline(),
            'created_at' => isset($this->created_at) ? $this->created_at : null,
        ];
    }

}
