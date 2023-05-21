<?php

namespace Webkul\Admin\Http\Resources\Category;


use Webkul\Category\Models\Category;
use Webkul\Category\Models\SubCategory;
use Illuminate\Http\Resources\Json\JsonResource;
use Webkul\Category\Http\Resources\SubCategoriesAll;
use Webkul\Admin\Http\Resources\Category\CategoriesAll;

class SubCategorySingle extends JsonResource
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
            'sold_count'         => $this->sold_count,
            'image'         => $this->image,
            'image_url'         => $this->image_url,
            'status'         => $this->status,
            'parent_categories'         => new CategoriesAll($this->parentCategories),
            'translations'         => $this->translations,
            'created_at'         => $this->created_at,
            'updated_at'         => $this->updated_at,
        ];
    }
}
