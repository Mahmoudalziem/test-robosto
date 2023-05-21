<?php


namespace Webkul\Admin\Http\Resources\Bundle;

use Illuminate\Http\Resources\Json\JsonResource;

class Area extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id'            => $request->id,
            'name'            => $request->name
        ];
    }
}
