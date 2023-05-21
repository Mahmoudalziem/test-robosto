<?php

namespace Webkul\Shipping\Http\Resources;


class ShippmentResource
{
    public static function DTO($data)
    {
        return [
            'shipping_number'          => 'creating',
            'merchant'=>$data["merchant"]??null,
            'shipping_address_id'         => $data["shipping_address_id"],
            'pickup_location_id' => $data["pickup_id"],
            'area_id'          => $data["area_id"],
            'warehouse_id' => $data["warehouse_id"],
            'items_count' => $data["items_count"]??0,
            'final_total' => $data["price"]??0,
            'note' => $data["note"] ?? '',
            'description' => $data["description"] ?? ''
        ];
    }
}
