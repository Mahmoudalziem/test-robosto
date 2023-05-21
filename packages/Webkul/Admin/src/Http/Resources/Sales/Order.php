<?php

namespace Webkul\Admin\Http\Resources\Sales;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Webkul\Promotion\Models\Promotion;

class Order extends JsonResource
{

    protected $append;

    public function __construct($resource, $append = null)
    {
        $this->append = $append;
        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @param  Request
     * @return array
     */
    public function toArray($request)
    {

        $orderLabels['has_notes'] = $this->notes->count() > 0 ? true : false;
        $orderLabels['has_feedback'] = $this->comment()->count() > 0 ? true : false;

        $paymentViaCard = null;
        if ($this->paymentViaCard) {
            $message = '';

            switch ($this->paymentViaCard->payload_response) {
                case isset($this->paymentViaCard->payload_response['data.message']):
                    $message = $this->paymentViaCard->payload_response['data.message'];
                    break;
                case isset($this->paymentViaCard->payload_response['detail']):
                    $message = $this->paymentViaCard->payload_response['detail'];
                    break;
                case isset($this->paymentViaCard->payload_response['message']):
                    $message = $this->paymentViaCard->payload_response['message'];
                    break;
                default:
                    $message = 'Unknown message';
                    break;
            }
            $paymentViaCard = [
                'is_paid' => $this->paymentViaCard->is_paid,
                'message' => $message
            ];
        }

        $ordersAddressCount = Order::where('address_id', $this->address_id)->count();
        $newAddress = ($ordersAddressCount == 1) ? true : false;
        $order = $this->formatOrderForShippingResponse($this);
        return [
            'id' => $order->id,
            'increment_id' => $order->increment_id,
            'status' => $order->status,
            'status_name' => $order->status_name_for_portal,
            'is_current' => $order->is_current,
            'source' => $order->channel->name,
            'order_flagged' => $order->order_flagged,
            'flagged_at' => $order->flagged_at,
            'area_id' => $order->area_id,
            'area' => $order->area->name,
            'shadow_area' => $order->shadowArea ? $order->shadowArea->name : null,
            'shadow_area_id' => $order->shadowArea ? $order->shadowArea->id : null,
            'warehouse' => $order->warehouse ? $order->warehouse->name : null,
            'driver' => $order->driver ? ['id' => $order->driver->id, 'name' => $order->driver->name] : null,
            'assigned_driver' => $order->assignedDriver ? $order->assignedDriver->name : null,
            'collector' => $order->collector ? ['id' => $order->collector->id, 'name' => $order->collector->name] : null,
            'order_date' => Carbon::parse($order->created_at)->format('d M Y h:i:s a'),
            'expected_on' => $order->expected_on ? $order->expected_on->format('d M Y h:i:s a') : null,
            'delivered_at' => $order->delivered_at ? Carbon::parse($order->delivered_at)->format('d M Y h:i:s a') : null,
            'scheduled_at' => $order->scheduled_at ? Carbon::parse($order->scheduled_at)->format('d M Y h:i:s a') : null,
            'payment_method' => $order->payment ? $order->payment->method : null,
            'payment_method_title' => $order->payment ? $order->payment->paymentMethod->title : null,
            'payment_via_card' => $paymentViaCard,
            'customer_id' => $order->customer_id,
            'customer_name' => $order->customer->name,
            'customer_mobile' => $order->customer->phone,
            'customer_address' => $order->address->address ?? null,
            'is_new_address' => $newAddress,
            'balance' => $order->customer_balance,
            'address_phone' => $order->address->phone ?? null,
            'order_comment' => $order->comment ? $order->comment->comment : null,
            'order_stars' => $order->comment ? $order->comment->rating : null,
            'order_note' => $order->note,
            'order_notes' => new OrderNotesAll($order->notes),
            'order_complaints' => new OrderComplaints($order->complaints),
            'has_notes' => $order->notes->count() > 0 ? true : false,
            'has_feedback' => $order->comment()->count() ? true : false, // feedback
            'order_lables' => $orderLabels,
            'order_sub_total' => $order->sub_total,
            'order_delivery_chargs' => $order->delivery_chargs,
            'order_tax_amount' => $order->tax_amount,
            'order_total' => $order->final_total,
            'promo_code' => $order->promotion ? $order->promotion->promo_code : $order->coupon_code,
            'discount_type' => $order->discount_type,
            'discount_value' => $order->discount,
            'discount_amount' => (float) round($order->calculateDiscount(), 2),
            'items_found_in_warehuses' => $order->items_found_in_warehuses,
            'cancelled_reason' => $order->cancelReason->reason ?? null,
            'who_cancelled_order' => $order->who_cancelled_order,
            'timeline' => $order->handleTimeline(),
            'items' => OrderItemResource::collection($order->items->sortBy('shelve_position')),
        ];
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
