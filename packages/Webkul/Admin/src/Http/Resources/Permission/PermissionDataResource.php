<?php

namespace Webkul\Admin\Http\Resources\Permission;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PermissionDataResource extends JsonResource {

    protected $append;

    public function __construct($resource, $append = null) {
        $this->append = $append;
        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @param  Request
     * @return array
     */
    public function toArray($request) {
        return [
            'id' => $this->id,
            'route_name' => $this->route_name,
            'label' => $this->name,
            'action' => $this->action,
            'slug' => $this->slug,
        ];
    }

}
