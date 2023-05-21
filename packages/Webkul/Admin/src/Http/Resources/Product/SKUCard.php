<?php

namespace Webkul\Admin\Http\Resources\Product;

use App\Http\Resources\CustomResourceCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Webkul\Inventory\Models\Warehouse;

class SKUCard extends CustomResourceCollection
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request
     * @return Collection
     */
    public function toArray($request)
    {
        $warehouses = Warehouse::all();

        return  $this->collection->map(function ($sku) use ($warehouses) {

            $qty = (int) $sku->qty;

            // Prepare Text
            if ($sku->type == 'purchase-orders-profile') {
                $warehouse = $warehouses->where('id', $sku->to_warehouse)->first();
                $text = __('admin::app.skuCardPurchaseText', ['qty' =>  $qty, 'warehouse' => $warehouse->name, 'time'   =>  $sku->s_date]);

            } elseif ($sku->type == 'order-profile') {
                $warehouse = $warehouses->where('id', $sku->from_warehouse)->first();
                $text = __('admin::app.skuCardSalesText', ['qty' =>  $qty, 'warehouse'   =>  $warehouse->name, 'time'   =>  $sku->s_date]);

            } elseif ($sku->type == 'transfers-profile') {
                $toWarehouse = $warehouses->where('id', $sku->to_warehouse)->first();
                $formWarehouse = $warehouses->where('id', $sku->from_warehouse)->first();
                $text = __('admin::app.skuCardTransactionText', ['qty' =>  $qty, 'to'   =>  $toWarehouse->name, 'from' => $formWarehouse->name, 'time'   =>  $sku->s_date]);

            } else {
                $warehouse = $warehouses->where('id', $sku->from_warehouse)->first();
                $text = __('admin::app.skuCardAdjustmentText', ['qty' =>  $qty, 'warehouse'   =>  $warehouse->name, 'time'   =>  $sku->s_date]);
            }

            return [
                'id'                => $sku->id,
                'text'              => $text,
                'sku'               => $sku->sku,
                'qty'               => $qty,
                's_date'            => $sku->s_date,
                'type'              => $sku->type,
                'type_id'           => $sku->type_id,
                'to_warehouse'      => $sku->to_warehouse ? $warehouses->find($sku->to_warehouse)->name : null,
                'from_warehouse'    => $sku->from_warehouse ? $warehouses->find($sku->from_warehouse)->name : null,
            ];
        });
    }

}
