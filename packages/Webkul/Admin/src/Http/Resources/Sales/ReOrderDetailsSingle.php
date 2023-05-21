<?php

namespace Webkul\Admin\Http\Resources\Sales;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Webkul\Promotion\Models\Promotion;

class ReOrderDetailsSingle extends JsonResource
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
            'id' => $this->id,
            'customer_name' =>  $this->customer->name,
            'customer_mobile' =>  $this->address->phone,
            'customer_address_name' => $this->address->name,
            'selected_address' => new SelectedAddress($this),
            'area_id' => $this->area_id,
            'customer_id' => $this->customer_id,
            'promo_code' => $this->promo_code,
            'items' => ReOrderItemResource::collection($this->items),
        ];

    }
}
