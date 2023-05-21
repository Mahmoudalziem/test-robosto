<?php


namespace Webkul\Admin\Http\Resources\PurchaseOrder;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Webkul\Admin\Http\Resources\Area\Area;
use App\Http\Resources\CustomResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderWarehousesSearch extends CustomResourceCollection
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request
     * @return Collection
     */
    public function toArray($request)
    {
        return  $this->collection->map(function ($warehouse) {
            return [
                'id' => $warehouse->id,
                'name' => $warehouse->name,
                'area_id' => $warehouse->area_id,
            ];
        });
    }

}