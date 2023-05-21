<?php

namespace Webkul\Driver\Http\Resources;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Webkul\Driver\Models\Driver;
use Webkul\Sales\Models\OrderItem;
use Illuminate\Http\Resources\Json\JsonResource;
use Webkul\Sales\Models\Order;
use Illuminate\Support\Collection;

class OrderSingle extends JsonResource
{


    protected $append;
    public function __construct($resource,$append=null)
    {
        $this->append=$append;
        parent::__construct($resource);

    }


    public function toArray($request)
    {
            if ($this->shippment_id) {
                if(!$this->customer){
                    $customer = new Collection();
                    if($this->warehouse){
                        $customer->name =  $this->warehouse->contact_name;
                        $customer->phone = $this->warehouse->contact_number;
                    }else{
                        $customer->name = "Shippment";
                        $customer->phone = "";
                    }
                    $this->customer = $customer;
                }else{
                    $this->customer->name ="Shippment To ".$this->customer->name;
                }
                $number = $this->shippment->shipping_number;
                $company = $this->shippment->shipper->name;
                $this->note = " شحنه رقم $number لشركة $company ";
            }
            $totalWeight=0;
            foreach($this->items as $item){
                $totalWeight=$totalWeight + ($item->item->weight * $item->qty_shipped);
            }
            return [
                'id'            => $this->id,
                'increment_id'   => $this->increment_id,
                'driver_name'   => $this->driver->name,
                'driver_availability'   => $this->driver->availability,
                'status'        => $this->status,
                'order_status'        =>$this->status,
                'status_name'   => $this->status_name,
                'items'     => OrderItemResource::collection($this->items->where('qty_shipped', '>', 0)->sortBy('shelve_position')),
                'bagsCount'  => 3,
                'order_date' => $this->created_at,
                'expected_order_date' => $this->created_at,
                'expected_delivered_date' => $this->expected_delivered_date,
                'payment_method' => null,
                'customer_name' =>  $this->customer->name . ' ( ' . $this->increment_id . ' )',
                //'customer_mobile' =>  config('robosto.ROBOSTO_PHONE'),
                'customer_mobile' =>  $this->customer->phone,
                'customer_address' => $this->address->address.'-(#'.$this->increment_id.')',
                'customerAddress' => $this->address,
                'customer_latitude' => $this->address->latitude,
                'customer_longitude' => $this->address->longitude,
                'order_total' => $this->is_paid == Order::ORDER_PAID ? 0 : $this->final_total,
                'order_note' => $this->note,
                //'items' => OrderItemResource::collection($this->items),
                'shipped_qty'     =>  $this->items_qty_shipped,
                'total_weight' => $totalWeight,
                'sub_total'   => (float) $this->sub_total,
                'delivery_fees' =>  (float) $this->delivery_chargs,
                'tax_amount' =>  (float) $this->tax_amount,
                'final_total'   => $this->is_paid == Order::ORDER_PAID ? 0 : $this->final_total,
                'collect_time'  => $this->items_qty_shipped * 10,
                'note'    => $this->note,
                'created_at'    => $this->created_at,
                'updated_at'    => $this->updated_at,
            ];


    }

}
