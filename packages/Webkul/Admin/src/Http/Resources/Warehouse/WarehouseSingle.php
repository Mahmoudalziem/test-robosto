<?php

namespace Webkul\Admin\Http\Resources\Warehouse;

use Illuminate\Http\Resources\Json\JsonResource;

class WarehouseSingle extends JsonResource {

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request) {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'contact_name' => $this->contact_name,
            'contact_email' => $this->contact_email,
            'contact_number' => $this->contact_number,
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'ar'         => ['name' => $this->translate('ar')->name],
            'en'         => ['name' => $this->translate('en')->name],
            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d') : null
        ];
    }

}
