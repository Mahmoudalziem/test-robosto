<?php

namespace Webkul\Shipping\Http\Resources;

use Webkul\Core\Models\Channel;
use Webkul\Customer\Models\Avatar;

class ShippingSystemNewCustomerResource
{
    public static function DTO($data)
    {

        $avatar = Avatar::first();
        return [
            'name'          => $data["shippment_shipping_address"]->name,
            'phone'         => $data["shippment_shipping_address"]->phone,
            'email'         => $data["shippment_shipping_address"]->email,
            'channel_id'=>  Channel::SHIPPING_SYSTEM,
            "is_verified" => 1,
            "status" => 1,
            "avatar_id"=>$avatar->id
        ];
    }
}
