<?php

namespace Webkul\Customer\Http\Resources\Customer;
use App\Http\Resources\CustomResourceCollection;

class CustomerCards extends CustomResourceCollection
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Support\Collection
     */
    public function toArray($request)
    {
        return  $this->collection->map(function ($card)  {
            return [
                'id'              => $card->id,
                'last_digits'     => $card->last_four ,
                'is_default'         => $card->is_default ,
                'brand'            => $card->brand,
            ] ;
        });
    }

}