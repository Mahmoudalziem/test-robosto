<?php

namespace Webkul\Admin\Http\Resources\User;

use Illuminate\Http\Resources\Json\JsonResource;

class DriverTransactionSingle extends JsonResource
{

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        return [
            "id" => $this->id,
            "amount" => $this->amount,
            "driver_current_wallet" => $this->driver->wallet - $this->amount,
            "driver_name" => $this->driver->name,
            "warehouse_name" => $this->warehouse->name,
            "area_name" => $this->area->name,
            "area_manager" => $this->admin ? $this->admin->name : null,
            'transaction_date' => $this->created_at,
            'status' => $this->status,
        ];
    }
}
