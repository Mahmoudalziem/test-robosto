<?php

namespace Webkul\Driver\Http\Resources\Driver;

use Illuminate\Http\Resources\Json\JsonResource;

use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Carbon;
use Webkul\Core\Models\Setting;


class DriverSingle extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        $today = Carbon::now()->format('Y-m-d').'%';
        $driverMotorLoggedToday=$this->motors()->wherePivot('created_at', 'like',$today)->count() >0 ? true:false;
        $supportPhone = Setting::where('key','driver_support_phone')->first();

        return [
            'id'            => $this->id,
            'area'          => $this->area->name,
            'warehouse'          => $this->warehouse->name,
            'warehouse_latitude'          => $this->warehouse->latitude,
            'warehouse_longitude'          => $this->warehouse->longitude,
            'name'          => $this->name,
            'username'          => $this->username,
            'email'         => $this->email,
            'id_number' => $this->id_number,
            'liecese_validity_no' => $this->liecese_validity_no,
            'liecese_validity_date'    => $this->liecese_validity_date,
            'wallet'    => (float) $this->wallet,
            'address' => $this->address,
            'phone_private' => $this->phone_private,
            'phone_work'    => $this->phone_work,
            'image'         => $this->image_url(),
            'liecese_image' => $this->imageIdUrl(),
            'isOnline'=>$this->is_online?true:false,
            'availability'=>$this->availability,
            'can_receive_orders' => $this->can_receive_orders,
            'driverMotorLoggedToday'=>$driverMotorLoggedToday,
            'status'        => $this->status,
            'driver_support_phone' => $supportPhone ? $supportPhone->value : null,
            'created_at'    => $this->created_at,
            'updated_at'    => $this->updated_at,
        ];
    }
}
