<?php

namespace Webkul\Sales\Http\Resources;

use App\Http\Resources\CustomResourceCollection;
use Webkul\Sales\Models\OrderItem;

class OrderItemsAll extends CustomResourceCollection
{


    protected $append;
    public function __construct($resource,$append=null)
    {
        $this->append=$append;
        parent::__construct($resource);

    }


    public function toArray($request)
    {

        return  $this->collection->map(function ($order) {
            $orderItems=OrderItem::with('item')->where('order_id',$order->id)->take(3)->get();

            $items=[];
            $itemsData=[];
            foreach ($orderItems as $key=>$item){
                    $items['product_id']=$item->product_id;
                    $items['product_name']=$item->item->name;
                    $items['image']=$item->item->image_url;
                    $items['qty']=$item->qty_shipped;
                    array_push($itemsData,$items);
            }

            $data= [
                'id'            => $order->id,
                'order_no'            => $order->increment_id,
                'price'            => $order->final_total,
                'order_sub_total' => $order->sub_total,
                'order_delivery_chargs' => $order->delivery_chargs,
                'order_tax_amount' => $order->tax_amount,
                'order_total' => $order->final_total,
                'payment_method' => $order->payment ? $order->payment->method : null,
                'payment_method_title' => $order->payment ? $order->payment->paymentMethod->title : null,
                'status'        => $order->status,
                'status_name'   => $order->status_name,
                'address'   =>  $order->address,
                'ratings'   =>  $order->comment?$order->comment->rating:null,
                'order_date'    => $order->created_at,
                'scheduled_at' => $order->scheduled_at,
                'orderItems'     => $itemsData,


            ];
            return $data;

        });
    }

}