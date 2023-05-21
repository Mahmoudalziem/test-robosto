<?php

namespace Webkul\Area\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AreaAddressSingle extends JsonResource
{
/**
* Transform the resource into an array.
*
* @param  Request  $request
* @return array
*/
public function toArray($request)
{


return [

'id' => isset($this->area)?$this->area->id:null,
'status' => isset($this->area)?$this->area->status:null,
'name' => isset($this->area)?$this->area->name:null,
'created_at' => isset($this->area)?$this->area->created_at:null,
'updated_at' => isset($this->area)?$this->area->updated_at:null,
];
}

}