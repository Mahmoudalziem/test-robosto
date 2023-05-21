<?php

namespace Webkul\Admin\Http\Resources\Category;


use Illuminate\Http\Resources\Json\JsonResource;

class CategorySingle extends JsonResource
{
    protected $append;
    public function __construct($resource, $append = null)
    {
        $this->append = $append;
        parent::__construct($resource);
    }
    public function toArray($request)
    {

        return [
            'id'            => $this->id,
            'name'         => $this->name,
            'position'         => $this->position,
            'image'         => $this->image,
            'image_url'         => $this->image_url,
            'status'         => $this->status,
            'sold_count'         => $this->sold_count,
            'sub_categories'         => new SubCategoryAll($this->subCategories),
            'translations'         => $this->translations,
            'created_at'         => $this->created_at,
            'updated_at'         => $this->updated_at,
        ];
    }
}
