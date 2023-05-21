<?php

namespace Webkul\Admin\Http\Resources\Customer;

use App\Http\Resources\CustomResourceCollection;

class CustomerInvitationAll extends CustomResourceCollection
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Support\Collection
     */
    public function toArray($request)
    {
        return  $this->collection->map(function ($customer) {

            return [
                'id' => $customer->id,
                'email'         => isset($customer->email) ? $customer->email : '-',
                'name'          => isset($customer->name) ? $customer->name : '-',
                'phone'         => isset($customer->phone) ? $customer->phone : '-',
                'landline' => (string) isset($customer->inviter->landline) ? $customer->landline : '-',
                'gender' =>   isset($customer->gender) && $customer->gender  ? 1 : 0,
                'avatar' => $customer->avatar_url,
                'source' => (string)   isset($customer->channel->name) ? $customer->channel->name : '-',
                'area' => (string)   isset($customer->default_address) ? $customer->default_address->area->name : '-',
            ];
        });
    }

}