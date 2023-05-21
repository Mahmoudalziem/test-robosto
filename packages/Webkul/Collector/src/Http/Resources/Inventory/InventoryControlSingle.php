<?php

namespace Webkul\Collector\Http\Resources\Inventory;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;
class InventoryControlSingle extends JsonResource {

    protected $append;

    public function __construct($resource, $append = null) {
        $this->append = $append;
        parent::__construct($resource);
    }

    public function toArray($request) {
        
        return [
            'id' => $this->id,
            'area' => $this->area['name'] ,
            'warehouse' => $this->warehouse['name'],
            'start_date' => Carbon::parse($this->start_date)->format('Y-m-d'),
            'end_date' => $this->end_date,
            'is_completed' => $this->is_completed,
            'is_active' => $this->is_active,
        ];
    }

}
