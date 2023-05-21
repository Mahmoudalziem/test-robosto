<?php

namespace Webkul\Admin\Http\Resources\Shelve;


use Illuminate\Http\Resources\Json\JsonResource;
use Webkul\Category\Models\Category;
use Webkul\Category\Models\SubCategory;

class ShelveSingle extends JsonResource
{
    protected $append;
    public function __construct($resource,$append=null)
    {
        $this->append=$append;
        parent::__construct($resource);

    }
    public function toArray($request)
    {
        return [
            'id'            => $this->id,
            'name'         => $this->name,
            'position'         => $this->position,
            'row'         => $this->row,
            'products'      => new Products($this->products),
            'created_at'    => $this->created_at,
        ];

    }

}