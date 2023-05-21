<?php

namespace Webkul\Admin\Http\Resources\Sales;

use App\Http\Resources\CustomResourceCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
                    array_push($itemsData,$items);
            }

            return [
                'id'            => $order->id,
                'status'        => $order->status,
                'status_name'   => $order->status_name,
                'orderItems'     => $itemsData,
                'created_at'    => $order->created_at,
                'updated_at'    => $order->updated_at,
            ];

        });
    }

}