<?php

namespace Webkul\Admin\Http\Resources\Supplier;

use App\Http\Resources\CustomResourceCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class SupplierAll extends CustomResourceCollection
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request
     * @return Collection
     */
    public function toArray($request)
    {
        return  $this->collection->map(function ($supplier) {
            return [
                'id'            => $supplier->id,
                'name'         => $supplier->name,
                'email'         => $supplier->email,
                'work_phone'         => $supplier->work_phone,
                'mobile_phone'         => $supplier->mobile_phone,
                'company_name'         => $supplier->company_name,
                'address_title'         => $supplier->address_title,
                'address_city'         => $supplier->address_city,
                'address_state'         => $supplier->address_state,
                'address_zip'         => $supplier->address_zip,
                'address_phone'         => $supplier->address_phone,
                'address_fax'         => $supplier->address_fax,
                'remarks'         => $supplier->remarks,
                'country'         => '',
                'areas'         => $supplier->areas,
                'status'         => $supplier->status,
                'created_at'    => $supplier->created_at,
            ];
        });
    }

}