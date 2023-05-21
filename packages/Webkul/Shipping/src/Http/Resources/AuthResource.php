<?php

namespace Webkul\Shipping\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AuthResource extends JsonResource
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
            'name'          => $this->name,
            'email'         => $this->email,
            'address' => $this->address,
            'phone_private' => $this->phone_private,
            'phone_work'    => $this->phone_work,
            'image_url'         => $this->image_url,
            'status'        => $this->status,
            'created_at'    => $this->created_at->format('Y-m-d')
        ];
    }
}
