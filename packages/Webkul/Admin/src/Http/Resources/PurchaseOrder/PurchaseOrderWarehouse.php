<?php


namespace Webkul\Admin\Http\Resources\PurchaseOrder;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Webkul\Admin\Http\Resources\Area\Area;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderWarehouse extends JsonResource
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
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'area_id' => $this->area_id,
        ];
    }

}