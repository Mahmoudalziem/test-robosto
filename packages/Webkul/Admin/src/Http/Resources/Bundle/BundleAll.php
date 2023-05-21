<?php

namespace Webkul\Admin\Http\Resources\Bundle;

use App\Http\Resources\CustomResourceCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use \Webkul\Area\Http\Resources\AreaAll;

class BundleAll extends CustomResourceCollection
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request
     * @return Collection
     */
    public function toArray($request)
    {
        return  $this->collection->map(function ($bundle) {
            return [
                'id'            => $bundle->id,
                'name'            => $bundle->name,
                'total_original_price'            => $bundle->total_original_price,
                'total_bundle_price'            => $bundle->total_bundle_price,
                'discount_type'            => $bundle->discount_type,
                'discount_value'            => $bundle->discount_value,
                'image_url'            => $bundle->image_url,
                'thumb_url'            => $bundle->thumb_url,
                'amount'            => $bundle->amount,
                'status'            => $bundle->status,
                'start_validity'            => $bundle->start_validity,
                'end_validity'            => $bundle->end_validity,
                'total_original_price'            => $bundle->total_original_price,
                'areas'                 => new  AreaAll( $bundle->areas()->get()  ),                 
                'area_id'                 =>  $bundle->area_id,
                'area_name'                 =>  $bundle->area->name,
                'items'                 =>  new BundleItem($bundle->items),
                'created_at'    => $bundle->created_at,
                'updated_at'    => $bundle->updated_at,
            ];
        });
    }
}
