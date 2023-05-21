<?php

namespace Webkul\Sales\Http\Resources;

use App\Http\Resources\CustomResourceCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class PaymentsAll extends CustomResourceCollection
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request
     * @return Collection
     */
    public function toArray($request)
    {
        return  $this->collection->map(function ($payment) {

            return [
                'id'            => $payment->id,
                'slug'            => $payment->slug,
                'title'            => $payment->title,
            ];
        });
    }

}
