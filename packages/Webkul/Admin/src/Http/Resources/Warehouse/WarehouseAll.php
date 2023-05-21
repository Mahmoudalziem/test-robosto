<?php

namespace Webkul\Admin\Http\Resources\Warehouse;

use App\Http\Resources\CustomResourceCollection;

class WarehouseAll extends CustomResourceCollection
{

    public function toArray($request)
    {
        return $this->collection->map(function ($warehouse) {

            return [
                'id' => $warehouse->id,
                'status' => $warehouse->status,
                'contact_name' => $warehouse->contact_name,
                'contact_email' => $warehouse->contact_email,
                'contact_number' => $warehouse->contact_number,
                'address' => $warehouse->address,
                'latitude' => $warehouse->latitude,
                'longitude' => $warehouse->longitude,
                'ar'         => ['name' => $warehouse->translate('ar')->name],
                'en'         => ['name' => $warehouse->translate('en')->name],
                'created_at' => $warehouse->created_at ? $warehouse->created_at->format('Y-m-d') : null
            ];
        });
    }
}
