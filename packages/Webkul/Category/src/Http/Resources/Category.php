<?php

namespace Webkul\Category\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class Category extends JsonResource
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
                'name'         => $this->name,
                'description'         => $this->description,
                'sub_categories' => new SubCategoriesAll($this->subCategories) 
        ];
    }

}