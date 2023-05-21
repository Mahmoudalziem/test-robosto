<?php

namespace Webkul\Sales\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CollectorResource extends JsonResource
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
            "id"    =>  $this->id,
            "name"  =>  $this->name,
            "id_number"  =>  $this->id_number,
            "address"   =>  $this->address,
            "image" =>  $this->image,
            "image_id" =>  $this->image_id,
            "image_url"  =>  $this->image_url(),
            "phone_private" =>  $this->phone_private,
            "phone_work"    =>  $this->phone_work,
            "username"  =>  $this->username,
            "email" =>  $this->email,
        ];

    }

}