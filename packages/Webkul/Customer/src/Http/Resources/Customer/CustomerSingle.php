<?php

namespace Webkul\Customer\Http\Resources\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Carbon;


class CustomerSingle extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request
     * @return array
     */
    public function toArray($request)
    {
        // if this new customer and used invitation code
        $firstOrder = false;
        if ($this->invitation_applied == 0 && !is_null($this->invited_by)) {
            $firstOrder = true;
            if ($this->activeOrders->isNotEmpty()) {
                $firstOrder = false;
            }
        }

        return [
            'id' => $this->id,
            'channel_id' => $this->channel_id,
            'name' => $this->name,
            'email' => $this->email,
            'gender' => $this->gender,
            'date_of_birth' => $this->date_of_birth,
            'default_address' => $this->getDefaultAddressID(), // from last order or last added address
            'addresses' => $this->addresses->where('covered', '1')->whereIn('area_id',[1,3])->all(),
            'phone' => $this->phone,
            'landline' => $this->landline,
            'referral_code' => $this->referral_code,
            'notes' => $this->notes,
            'wallet' => $this->wallet,
            'is_flagged' => $this->is_flagged,
            'otp_verified' => $this->otp_verified,
            "avatar" => $this->myAvatar ? $this->avatar : $this->handleDefaultAvatar()->image,
            "avatar_url" => $this->myAvatar ? $this-> avatar_url : $this->handleDefaultAvatar()->image_url,
            "avatar_id" => $this->myAvatar ? $this->avatar_id  : $this->handleDefaultAvatar()->id,
            'first_order' => $firstOrder,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}