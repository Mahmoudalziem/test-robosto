<?php

namespace Webkul\Shipping\Http\Resources;

use App\Http\Resources\CustomResourceCollection;

class ShippingAddressResource
{
    public static function DTO($data)
    {
        $phone = $data["customer_phone"];
        preg_match_all('/(0)(1)[0-9]{9}/',$phone,$matches);
        $tmp = $matches[0];
        if(count($tmp) > 0){
            $phone = $tmp[0];
        }
        return [
            'name'          => $data["customer_name"],
            'email'         => $data["customer_email"]??'-',
            'address' => $data["customer_address"],
            'phone' => $phone,
            'landmark'=>$data["customer_landmark"]??'-',
            'apartment_no'=>$data["customer_apartment_no"]??'-',
            'building_no'=>$data["customer_building_no"]??'-',
            'floor_no'=>$data["customer_floor_no"]??'-',
        ];
    }
}
