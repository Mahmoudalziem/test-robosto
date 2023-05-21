<?php

namespace Webkul\Admin\Http\Resources\Supllier;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class Supplier extends JsonResource
{
    protected $append;
    public function __construct($resource,$append=null)
    {
        $this->append=$append;
        parent::__construct($resource);

    }

    /**
     * Transform the resource into an array.
     *
     * @param  Request
     * @return array
     */
    public function toArray($request)
    {

        return [
                'id'            => $this->id,
                'name'         => $this->name,
                'email'         => $this->email,
                'work_phone'         => $this->work_phone,
                'mobile_phone'         => $this->mobile_phone,
                'company_name'         => $this->company_name,
                'address_title'         => $this->address_title,
                'address_city'         => $this->address_city,
                'address_state'         => $this->address_state,
                'address_zip'         => $this->address_zip,
                'address_phone'         => $this->address_phone,
                'address_fax'         => $this->address_fax,
                'remarks'         => $this->remarks,
                'country'         => '',
                'status'         => $this->status,
                'created_at'    => $this->created_at,
        ];

    }

}