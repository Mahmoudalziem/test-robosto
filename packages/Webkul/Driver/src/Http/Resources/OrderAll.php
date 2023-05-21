<?php

namespace Webkul\Driver\Http\Resources;


use Carbon\Carbon;
use Illuminate\Http\Request;
use Webkul\Sales\Models\Order;
use Illuminate\Support\Collection;
use App\Http\Resources\CustomResourceCollection;

class OrderAll extends CustomResourceCollection
{

    /**
     * Transform the resource into an array.
     *
     * @param Request
     * @return Collection
     */
    public function toArray($request)
    {
        return $this->collection->map(function ($order) {

            $totalWeight = 0;
            foreach ($order->items as $item) {
                $totalWeight = $totalWeight + ($item->item->weight * $item->qty_shipped);
            }
            $order = $this->formatOrderForShippingResponse($order);
            return [
                'id' => $order->id,
                'increment_id' => $order->increment_id,
                'items_count' => $order->items_count,
                'no_of_qty' => $order->items_qty_shipped,
                'status' => $order->status,
                'status_name' => $order->status_name_for_portal,
                'amount_to_pay' => $order->is_paid == Order::ORDER_PAID ? 0 : $order->final_total,
                'payment_method' => $order->payment ? $order->payment->method : null,
                'payment_method_title' => $order->payment ? $order->payment->paymentMethod->title : null,
                'address' => $order->address ? $order->address->address : null,
                'customer_name' => $order->customer->name,
                // 'contact_customer' => config('robosto.ROBOSTO_PHONE'),
                'contact_customer' => $order->customer->phone,
                'items'     => OrderItemResource::collection($order->items->where('qty_shipped', '>', 0)->sortBy('shelve_position')),
                'driver_name'   => $order->driver->name,
                'driver_availability'   => $order->driver->availability,
                'order_status'        => $order->status,
                'status_name'   => $order->status_name,
                'bagsCount'  => 3,
                'order_date' => $order->created_at,
                'expected_order_date' => $order->created_at,
                'expected_delivered_date' => $order->expected_delivered_date,
                'customer_mobile' =>  $order->customer->phone,
                'customer_address' => $order->address->address . '-(#' . $order->increment_id . ')',
                'customerAddress' => $order->address,
                'customer_latitude' => $order->address->latitude,
                'customer_longitude' => $order->address->longitude,
                'order_total' => $order->is_paid == Order::ORDER_PAID ? 0 : $order->final_total,
                'order_note' => $order->note,
                'shipped_qty'     =>  $order->items_qty_shipped,
                'total_weight' => $totalWeight,
                'sub_total'   => (float) $order->sub_total,
                'delivery_fees' =>  (float) $order->delivery_chargs,
                'tax_amount' =>  (float) $order->tax_amount,
                'final_total'   => $order->is_paid == Order::ORDER_PAID ? 0 : $order->final_total,
                'collect_time'  => $order->items_qty_shipped * 10,
                'note'    => $order->note,
                'created_at'    => $order->created_at,
                'updated_at'    => $order->updated_at,
            ];
        });
    }

    public function formatOrderForShippingResponse($order){
        if ($order->shippment_id) {
            if(!$order->customer){
                $customer = new Collection();
                if($order->warehouse){
                    $customer->name =  $order->warehouse->contact_name;
                    $customer->phone = $order->warehouse->contact_number;
                }else{
                    $customer->name = "Shippment";
                    $customer->phone = "";
                }
                $order->customer = $customer;
            }else{
                $order->customer->name ="Shippment To ".$order->customer->name;
            }
        }
        return $order;
    }
}
