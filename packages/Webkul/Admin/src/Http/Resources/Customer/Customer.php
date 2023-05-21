<?php

namespace Webkul\Admin\Http\Resources\Customer;

use App\Http\Resources\CustomResourceCollection;

class Customer extends CustomResourceCollection
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
                'id'            => $customer->id,
                'email'         => isset($customer->email)?$customer->email:'-',
                'name'          => isset($customer->name)?$customer->name:'-',
                'phone'         => isset($customer->phone)?$customer->phone:'-',
                'landline' => (string) isset($customer->landline)?$customer->landline:'-',
                'gender' =>  isset($customer->gender) ?1:0,
                'status' => (boolean) isset($customer->status)?$customer->status:null ,
                'is_online' => (boolean)  $customer->is_online  ,
                'otp_verified' => (boolean)  isset($customer->otp_verified)?$customer->otp_verified:null,
                'is_flagged' => (boolean)  isset($customer->is_flagged)?$customer->is_flagged:null,
                'source' => (string)   isset($customer->channel->name)?$customer->channel->name:'-' ,
                'wallet' => (double)  isset($customer->wallet)?$customer->wallet:0 ,
                'avatar' => $customer->avatar_url,
                'orders_count' => $customer->orders?$customer->orders->count():0,
                'devices' => $customer->deviceToken ? new CustomerDevices($customer->deviceToken()->groupBy('device_type')->get()) : 0,
                // 'invitationsLogs_count'=>$customer->invitationsLogs?$customer->invitationsLogs->count():0,
                'invitationsLogs_count'=>count($customer->inviters()),
                'invited_by'=> $customer->invitedBy ? $customer->invitedBy->name : null,
                'created_at'    => isset($customer->created_at)?$customer->created_at:null,
            ];
        });
    }

}