<?php

namespace Webkul\Promotion\Http\Resources;

use App\Http\Resources\CustomResourceCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class PromotionExceptionItems extends CustomResourceCollection
{
    protected $applyType;

    public function __construct($resource, $applyType = null)
    {
        $this->applyType = $applyType;

        parent::__construct($resource);
    }


    /**
     * Transform the resource into an array.
     *
     * @param  Request
     * @return Collection
     */
    public function toArray($request)
    {
        return  $this->collection->map(function ($type) {
            return [
                'id'            => $type->id,
                'name'         => $type->name
            ];
        });
    }
}
