<?php

namespace Webkul\Promotion\Http\Resources;

use App\Http\Resources\CustomResourceCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class PromotionApplyTypes extends CustomResourceCollection
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
            
            if ($this->applyType == 'category') {
                $modelType = $type->category;
            } elseif ($this->applyType == 'subCategory') {
                $modelType = $type->subCategory;
            } elseif ($this->applyType == 'product') {
                $modelType = $type->product;
            } elseif ($this->applyType == 'boundl') {
                $modelType = $type->product;
            } else {
                $modelType = $type->product;
            }

            return [
                'id'            => $modelType->id,
                'name'         => $modelType->name
            ];
        });
    }
}
