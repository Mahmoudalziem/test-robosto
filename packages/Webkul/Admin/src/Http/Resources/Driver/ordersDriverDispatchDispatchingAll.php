<?php

namespace Webkul\Admin\Http\Resources\Driver;


use App\Http\Resources\CustomResourceCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Webkul\Sales\Models\OrderDriverDispatch;
use Webkul\Sales\Models\OrderLogsActual;
use Webkul\Sales\Models\OrderLogsEstimated;

class ordersDriverDispatchDispatchingAll extends CustomResourceCollection
{
    /**
     * Transform the resource into an array.
     *
     * @param Request
     * @return Collection
     */
    public function toArray($request)
    {


        return $this->collection->map(function ($orderDisptatching) {



            return [
                'id' => $orderDisptatching->order_id,
                'order_id' => $orderDisptatching->order->increment_id,
                'order_date' => $orderDisptatching->order->created_at,
                'request_status'=>$orderDisptatching->status,
                'cancellation_reason'=>$orderDisptatching->reason,

            ];
        });
    }

}