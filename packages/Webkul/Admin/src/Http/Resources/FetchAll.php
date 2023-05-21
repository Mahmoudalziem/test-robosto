<?php

namespace Webkul\Admin\Http\Resources;

use App\Http\Resources\CustomResourceCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Webkul\Core\Models\Shelve;
use Webkul\Product\Models\Product;
use Webkul\Sales\Models\PaymentMethod;

class FetchAll extends CustomResourceCollection {

    /**
     * Transform the resource into an array.
     *
     * @param  Request
     * @return Collection
     */
    public function toArray($request) {
        return $this->collection->map(function ($model) {

                    $name = $model->name;

                    if ($model instanceof Shelve) {
                        $name = $model->name . $model->row;
                    }
                    
                    if ($model instanceof PaymentMethod) {
                        $name = $model->title;
                    }

                    $data = [
                        'id' => $model->id,
                        'name' => $name,
                        'image' => $model->image,
                        'image_url' => $model->image ? $model->image_url : null,
                    ];
                    if ($model instanceof Product) {
                        
                        $data['price'] = $model->price ;
                        $data['label_name'] = $model->label_name ;                        
                    }                    
 
                    return $data;
                });
    }

}
