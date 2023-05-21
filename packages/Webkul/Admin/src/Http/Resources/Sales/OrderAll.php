<?php

namespace Webkul\Admin\Http\Resources\Sales;

use App\Http\Resources\CustomResourceCollection;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Webkul\Sales\Models\Order;

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
            $ordersAddressCount = Order::where('address_id', $order->address_id)->count();
            $newAddress = ($ordersAddressCount == 1) ? true : false;
            $order = $this->formatOrderForShippingResponse($order);
            return [
                'id' => $order->id,
                'increment_id' => $order->increment_id,
                'items_count' => $order->items_count,
                'source' => (string) isset($order->channel->name) ? $order->channel->name : '-',
                'no_of_qty' => $order->items_qty_shipped,
                'status' => $order->status,
                'is_current' => $order->is_current,
                'status_name' => $order->status_name_for_portal,
                'order_flagged' => $order->order_flagged,
                'flagged_at' => $order->flagged_at,
                'has_notes' => $order->notes->count() > 0 ? true : false,
                'has_feedback' => $order->comment()->count() ? true : false, // feedback                
                'price' => $order->sub_total + $order->delivery_chargs + $order->tax_amount,
                'amount_to_pay' => $order->final_total,
                'promo_code' => $order->promotion ? $order->promotion->promo_code : null,
                'discount_type' => $order->discount_type,
                'discount_value' => $order->discount,
                'payment_method' => $order->payment ? $order->payment->method : null,
                'payment_method_title' => $order->payment ? $order->payment->paymentMethod->title : null,
                'area' => $order->area->name,
                'area_id' => $order->area_id,
                'shadow_area' => $order->shadowArea ? $order->shadowArea->name : null,
                'shadow_area_id' => $order->shadowArea ? $order->shadowArea->id : null,
                'warehouse' => $order->warehouse ? $order->warehouse->name : null,
                'driver' => $order->driver ? $order->driver->name : null,
                'address' => $order->address ? $order->address->address : null,
                'customer_name' => $order->customer->name,
                'contact_customer' => $order->customer->phone,
                'balance' => $order->customer_balance,
                'is_new_address' => $newAddress,
                'order_date' => Carbon::parse($order->created_at)->format('d M Y h:i:s a'),
                'expected_on' => $order->expected_on ? $order->expected_on->format('d M Y h:i:s a') : null,
                'delivered_at' => $order->delivered_at ? Carbon::parse($order->delivered_at)->format('d M Y h:i:s a') : null,
                'cancelled_reason' => $order->cancelled_reason,
            ];
        });
    }

    public function formatOrderForShippingResponse($order){
        if ($order->shippment_id) {
            if(!$order->customer){
                $customer = new Collection();
                if($order->warehouse){
                    $customer->name = "Shippment To ".$order->warehouse->contact_name;
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
