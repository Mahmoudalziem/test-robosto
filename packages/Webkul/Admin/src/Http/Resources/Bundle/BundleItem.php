<?php


namespace Webkul\Admin\Http\Resources\Bundle;

use App\Http\Resources\CustomResourceCollection;

class BundleItem extends CustomResourceCollection
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return $this->collection->map(function ($bundle) {
            return [
                'item_id'            => $bundle->id,
                'id'            => $bundle->product->id,
                'name'            => $bundle->product->name,
                'image_url'            => $bundle->product->image_url,
                'quantity'            => $bundle->quantity,
                'original_price'            => $bundle->original_price,
                'bundle_price'            => $bundle->bundle_price,
                'total_original_price'            => $bundle->total_original_price,
                'total_bundle_price'            => $bundle->total_bundle_price,
                'total_original_price'            => $bundle->total_original_price,
            ];
        });
    }
}
