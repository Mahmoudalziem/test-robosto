<?php

namespace Webkul\Promotion\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class Promotion extends JsonResource
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
                'image_url'            => $this->image_url,
                'thumb_url'            => $this->thumb_url,
                'price'            => $this->price,
                'tax'            => $this->tax,
                'weight'            => $this->weight,
                'width'            => $this->width,
                'height'            => $this->height,
                'length'            => $this->length,
                'unit_name' => $this->unit->name,
                'unit_value'            => $this->unit_value,
                'name'         => $this->name,
                'description'         => $this->description,
                'total_in_stock'         => $this->total_in_stock,
        ];
    }

}