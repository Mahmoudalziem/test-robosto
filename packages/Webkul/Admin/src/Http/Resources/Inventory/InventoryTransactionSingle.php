<?php

namespace Webkul\Admin\Http\Resources\Inventory;

use Illuminate\Http\Resources\Json\JsonResource;

class InventoryTransactionSingle extends JsonResource {

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
            $status = 'On the way';
        } elseif ($this->status == 3) {
            $status = 'Transffered';
        }

        $products = $this->transactionProducts;
        $totalCost = 0;
        $prodcutList = [];
        foreach ($this->transactionProducts as $prod) {
            $prodcutObj['id'] = $prod->id;
            $productObj = $prod->product;
            $productObj['unit'] = $prod->product->unit;
            $prodcutObj['product_id'] = $prod->product->id;
            $prodcutObj['productObj'] = $productObj;
            $prodcutObj['productName'] = $prod->product->name;
            $prodcutObj['sku'] = $prod->sku;
            $prodcutObj['qty'] = $prod->qty;

            $prodcutObj['cost'] = $prod->purchaseOrder->cost ?? 0;
            $prodcutObj['inventory_transaction_id'] = $prod->inventory_transaction_id;

            $totalCost += $prod->qty * $prodcutObj['cost'];
            array_push($prodcutList, $prodcutObj);
        }

        return [
            'id' => $this->id,
            'source' => isset($this->from_warehouse_id) ? $this->fromWarehouse->name : '-',
            'sourceArea' => isset($this->from_warehouse_id) ? $this->fromWarehouse->area->name : '-',
            'destinasation' => isset($this->to_warehouse_id) ? $this->toWarehouse->name : '-',
            'destinasationArea' => isset($this->to_warehouse_id) ? $this->toWarehouse->area->name : '-',
            'total_cost' => $totalCost,
            'transaction_type' => $this->transaction_type == 'inside' ? __('admin::app.inside') : __('admin::app.outside'),
            'status_id' => $this->status,
            'created_by' => $this->createdBy ? $this->createdBy->name : null,
            'status' => $status,
            'products' => $prodcutList,
            'created_at' => isset($this->created_at) ? $this->created_at : null,
        ];
    }

}
