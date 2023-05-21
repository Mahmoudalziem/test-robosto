<?php

namespace Webkul\Admin\Http\Resources\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class Product extends JsonResource
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
                'barcode'            => $this->barcode,
                'prefix'            => $this->prefix,
                'image'            => $this->image,
                'thumb'            => $this->thumb,
                'image_url'            => $this->image_url,
                'thumb_url'            => $this->thumb_url,
                'featured'            => $this->featured,
                'status'            => $this->status,
                'minimum_stock' => $this->minimum_stock,
                'returnable'            => $this->returnable,
                'price'            => $this->price,
                'cost'            => $this->cost,
                'tax'            => $this->tax,
                'weight'            => $this->weight,
                'width'            => $this->width,
                'height'            => $this->height,
                'length'            => $this->length,
                'brand_id'            => $this->brand_id,
                'unit_id'            => $this->unit_id,
                'unit_value'            => $this->unit_value,
                'name'         => $this->name,
                'description'         => $this->description,
                'note'         => $this->note,
                'shelve'         => new Shelve($this->shelve),
                'stock_on_hand'         => new AreaStock($this->areas, $this),
                'unit' => $this->unit,
                'brand' => $this->brand,
                'suppliers' => $this->suppliers,
                'areas' => $this->areas,
                'warehouses' => $this->warehouses,
                'inventoryProducts' => new ProductSKUs($this->inventoryProducts),
                'subCategories' => $this->subCategories,
                'translations' => $this->translations,
                'tags' => $this->tags,  
                'label' => $this->label,            
                'created_at'    => $this->created_at,
                'updated_at'    => $this->updated_at,
            ];
    }

}