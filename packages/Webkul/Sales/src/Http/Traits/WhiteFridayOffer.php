<?php

namespace Webkul\Sales\Http\Traits;

use Webkul\Area\Models\Area;
use Webkul\Sales\Models\Order;
use Webkul\Core\Models\Channel;
use Illuminate\Support\Facades\Log;
use Webkul\Customer\Models\CustomerAddress;
use Illuminate\Database\Eloquent\Collection;

trait WhiteFridayOffer
{

    /**
     * @param array $data
     *
     * @return array
     */
    public function applyWhiteFridayOffer(array $data)
    {
        Log::info('Check WhiteFridayOffer Products');

        $validProducts = collect(config('robosto.WHITE_FRIDAY_PRODUCTS'));
        if(!$validProducts->count()){
            return $data;
        }
        $customerOrders = Order::with('items')->where('status','!=',Order::STATUS_CANCELLED)->where('customer_id', auth('customer')->id())->get();
        $allItems = $this->mergeOrdersItems($customerOrders);

        foreach ($data['items'] as $key => $item) {

            $product = $validProducts->where('product_id', $item['id'])->where('from', '<=', now()->format('Y-m-d H:i:s'))->where('to', '>=', now()->format('Y-m-d H:i:s'))->first();

            if ($product) {

                $sameProductOrderedBefore = $allItems->where('product_id', $product['product_id'])->whereBetween('created_at', [$product['from'], $product['to']]);

                // If the customer reached to the end of the stock.
                if ($sameProductOrderedBefore->sum('qty_shipped') >= $product['max_qty']) {
                    unset($data['items'][$key]);
                    continue;
                }

                // if the customer ordered this product before
                if ($sameProductOrderedBefore->count()) {
                    // Get Ordered Qty before and recent qty
                    $allQtyOrdered = $sameProductOrderedBefore->sum('qty_shipped') + $item['qty'];

                    if ($allQtyOrdered < $product['max_qty']) {
                        continue;
                    } else {
                        $remainingQty = $product['max_qty'] - $sameProductOrderedBefore->sum('qty_shipped');
                        $data['items'][$key]['qty'] = $remainingQty;
                    }
                } else {

                    if ($item['qty'] > $product['max_qty']) {
                        $data['items'][$key]['qty'] = $product['max_qty'];
                        continue;
                    }
                }
            }
        }

        return $data;
    }

    /**
     * @param Collection $orders
     *
     * @return Collection
     */
    private function mergeOrdersItems($orders)
    {
        $allItems = collect();
        foreach ($orders as $order) {
            $allItems = $allItems->merge($order->items->toArray());
        }
        return $allItems;
    }
}
