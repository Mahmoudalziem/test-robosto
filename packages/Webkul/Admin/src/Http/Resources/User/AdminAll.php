<?php

namespace Webkul\Admin\Http\Resources\User;

use App\Http\Resources\CustomResourceCollection;
use Carbon\Carbon;
use Webkul\Sales\Models\OrderLogsActual;

class AdminAll extends CustomResourceCollection {

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Support\Collection
     */
    public function toArray($request) {

        return $this->collection->map(function ($user) {

                    return [
                "id" => $user->id,
                "name" => $user->name,
                "email" => $user->email,
                "username" => $user->username,
                "id_number" => $user->id_number,
                "is_verified" => $user->is_verified,
                "status" => $user->status,
                "address" => $user->address,
                "phone_work" => $user->phone_work,
                "phone_private" => $user->phone_private,
                "image" => $user->image_url,
                "areas" => $user->areas->map(function ($area) {
                    return ['id' => $area['id'], 'name' => $area['name']];
                }),
                "warehouses" => $user->warehouses->map(function ($warehouses) {
                    return ['id' => $warehouses['id'], 'name' => $warehouses['name']];
                }),
                "roles" => $user->getRoleNames(),
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
                    ];
                });
    }

}
