<?php

namespace Webkul\Shipping\Http\Resources;

use Webkul\Customer\Models\AddressIcon;

class ShippingSystemCustomerAddressResource
{
    public static function DTO($customer ,$data)
    {
        return [
            'customer_id'          => $customer->id,
            'name'              =>"Shippment Address",
            'covered'       =>'0',
            'icon_id'           =>AddressIcon::first()->id,
            'area_id'          => $data["nearest_warehouse"]->area_id,
            'address'          => $data["shippment_shipping_address"]->address,
            'floor_no'          => $data["shippment_shipping_address"]->floor_no,
            'apartment_no'          => $data["shippment_shipping_address"]->apartment_no,
            'building_no'          => $data["shippment_shipping_address"]->building_no,
            'landmark'          => $data["shippment_shipping_address"]->landmark,
            'latitude'         => $data["customer_location"]["lat"],
            'longitude'         => $data["customer_location"]["lng"],
            'phone'=>  $customer->phone,
        ];
    }
}
