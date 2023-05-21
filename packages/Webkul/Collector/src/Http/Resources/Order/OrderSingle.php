<?php

namespace Webkul\Collector\Http\Resources\Order;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Webkul\Sales\Models\OrderItem;
use Illuminate\Support\Facades\Config;
use Webkul\Promotion\Models\Promotion;
use Illuminate\Http\Resources\Json\JsonResource;
use Webkul\Sales\Models\OrderItemSku;

class OrderSingle extends JsonResource
{

    protected $append;

    public function __construct($resource, $append = null)
    {
        $this->append = $append;
        parent::__construct($resource);
    }

    public function toArray($request)
    {
        $totalWeight = 0;
        foreach ($this->items as $item) {
            $totalWeight += ($item->weight * $item->qty_shipped);
        }
        $skus = OrderItemSku::where('order_id', $this->id)->get();
        $allSKus = $skus->toArray();
        $orderItems = $this->items->where('qty_shipped', '>', 0)->sortBy('shelve_position');

        $allOrderItems = [];
        foreach ($orderItems as $item) {
            $orderItemQty = $item->qty_shipped;
            if ($item->bundle_id) {

                foreach ($item->bundleItems as $bundeItem) {
                    $bundeItemQty = $orderItemQty * $bundeItem['quantity'];
                    foreach ($allSKus as $k => $sku) {

                        if ($allSKus[$k]['product_id'] == $bundeItem['product_id'] && $bundeItemQty > 0) {
                            if ($allSKus[$k]['qty'] >= $bundeItemQty) {
                                $qty = $bundeItemQty;
                                $allSKus[$k]['qty'] = $allSKus[$k]['qty'] - $bundeItemQty;
                                $bundeItemQty = 0;
                            } elseif ($allSKus[$k]['qty'] < $bundeItemQty) {
                                $qty = $allSKus[$k]['qty'];
                                $bundeItemQty = $bundeItemQty - $allSKus[$k]['qty'];
                                $allSKus[$k]['qty'] = 0;
                            }

                            $allOrderItems[$item['product_id']][] = [
                                'product_id' => $bundeItem['product_id'],
                                'barcode' => substr($item->item->barcode, -4),
                                'sku' => $item->item->barcode,//$allSKus[$k]['sku'],
                                'qty' => $qty,
                                'order_id' => $item['order_id']
                            ];
                            $allSKus[$k]['qty'] = $allSKus[$k]['qty'];

                            if ($allSKus[$k]['qty'] == 0) {
                                unset($allSKus[$k]);
                            }
                        }
                    }
                }
            } else {
                foreach ($allSKus as $k => $sku) {

                    if ($allSKus[$k]['product_id'] == $item['product_id'] && $orderItemQty > 0) {
                        if ($allSKus[$k]['qty'] >= $orderItemQty) {
                            $qty = $orderItemQty;
                            $allSKus[$k]['qty'] = $allSKus[$k]['qty'] - $orderItemQty;
                            $orderItemQty = 0;
                        } elseif ($allSKus[$k]['qty'] < $orderItemQty) {
                            $qty = $allSKus[$k]['qty'];
                            $orderItemQty = $orderItemQty - $allSKus[$k]['qty'];
                            $allSKus[$k]['qty'] = 0;
                        }
                        $allOrderItems[$item['product_id']][] = [
                            'product_id' => $item['product_id'],
                            'barcode' => substr($item->item->barcode, -4),
                            'sku' => $item->item->barcode,//$allSKus[$k]['sku'],
                            'qty' => $qty,
                            'order_id' => $item['order_id']
                        ];
                        if ($allSKus[$k]['qty'] == 0) {
                            unset($allSKus[$k]);
                        }
                    }
                }
            }
        }
        $append['orderItemSkus'] = $allOrderItems;
        $append['orderItemIDs'] = array_keys($allOrderItems);
        if($this->shippment){
            $append['shippiment_tracking_number'] =  $this->shippment->shipping_number;
        }
        return [
            'id' => $this->id,
            'increment_id' => $this->increment_id,
            'driver_name' => $this->driver->name,
            'status' => $this->status,
            'status_name' => $this->status_name,
            'bagsCount' => 3,
            'remaining_orders' => $this->append['orders_count'] - 1,
            'shipped_qty' => $this->items_qty_shipped,
            // 'total_weight' => $totalWeight,
            'coupon_code' => $this->coupon_code,
            'customer_balance' => $this->customer_balance < 0 ? $this->customer_balance : null,
            'discount' => $this->discount,
            'discount_amount' => (float) round($this->calculateDiscount(), 2),
            'sub_total' => (float) $this->sub_total,
            'delivery_fees' => (float) $this->delivery_chargs,
            'tax_amount' => (float) $this->tax_amount,
            'final_total' => (float) $this->final_total,
            'collect_time' => $this->items_qty_shipped * 10,
            'note' => $this->note,
            'total_weight' => $totalWeight,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            //'orderItems' => OrderItemResource::collection($this->items->where('qty_shipped', '>', 0)->sortBy('shelve_position')),
            'orderItems' => OrderItemResource::customCollection($this->items->where('qty_shipped', '>', 0)->sortBy('shelve_position'), $append),

        ];
    }

    /**
     * @return float
     */
    private function calculateDiscount()
    {
        if ($this->coupon_code && !$this->promotion_id) {
            return $this->calculateGiftDiscount();
        }

        return $this->calculateItemsDiscount();
    }

    /**
     * @return float
     */
    private function calculateItemsDiscount()
    {
        $discount = 0;
        foreach ($this->items as $item) {
            if ($item->discount_type == Promotion::DISCOUNT_TYPE_PERCENT) {

                $discount += (($item->discount_amount / 100) * $item->base_total);
            } else {

                $discount += $item->discount_amount;
            }
        }
        return $discount;
    }

    /**
     * @return float
     */
    private function calculateGiftDiscount()
    {
        return (($this->discount / 100) * $this->sub_total);
    }
}