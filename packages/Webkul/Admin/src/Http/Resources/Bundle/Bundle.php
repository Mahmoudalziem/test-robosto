<?php

namespace Webkul\Admin\Http\Resources\Bundle;

use Illuminate\Http\Request;
use Webkul\Admin\Http\Resources\Bundle\Area;
use Illuminate\Http\Resources\Json\JsonResource;
use \Webkul\Area\Http\Resources\AreaAll;

class Bundle extends JsonResource
{

    protected $append;
    public function __construct($resource,$append=null)
    {
        $this->append=$append;
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
        return [
                'id'            => $this->id,
                'name'            => $this->name,
                'total_original_price'            => $this->total_original_price,
                'total_bundle_price'            => $this->total_bundle_price,
                'discount_type'            => $this->discount_type,
                'discount_value'            => $this->discount_value,
                'image_url'            => $this->image_url,
                'thumb_url'            => $this->thumb_url,
                'amount'            => $this->amount,
                'status'            => $this->status,
                'start_validity'            => $this->start_validity,
                'end_validity'            => $this->end_validity,
                'total_original_price'            => $this->total_original_price,
                'areas'                 => new  AreaAll( $this->areas()->get()  ), 
                'area_id'                 =>  $this->area_id,
                'area_name'                 =>  $this->area->name,
                'items'                 =>  new BundleItem($this->items),
                'translations'         => $this->translations,
                'created_at'    => $this->created_at,
                'updated_at'    => $this->updated_at,
            ];
    }

}