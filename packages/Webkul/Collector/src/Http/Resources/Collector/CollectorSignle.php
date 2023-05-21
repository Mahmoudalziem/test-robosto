<?php

namespace Webkul\Collector\Http\Resources\Collector;

use Illuminate\Http\Resources\Json\JsonResource;

use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Carbon;


class CollectorSignle extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {


        return [
            'id'            => $this->id,
            'area_id'         => $this->area_id,
            'area'         => $this->area->name,
            'warehouse_id'         => $this->warehouse_id,
            'warehouse'         => $this->warehouse->name,
            'image'         => $this->image_url(),
            'image_id'         => $this->imageIdUrl(),
            'name'          => $this->name,
            'email'          => $this->email,
            'username'          => $this->username,
            'address' => $this->address,
            'phone_private' => $this->phone_private,
            'phone_work'    => $this->phone_work,
            'availability'=>$this->availability,
            'status'        => $this->status,
            'created_at'    => $this->created_at,
            'updated_at'    => $this->updated_at,
        ];
    }
}