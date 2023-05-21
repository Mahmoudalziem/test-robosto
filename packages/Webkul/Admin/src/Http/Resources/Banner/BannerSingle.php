<?php

namespace Webkul\Admin\Http\Resources\Banner;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class BannerSingle extends JsonResource {

    protected $append;

    public function __construct($resource, $append = null) {
        $this->append = $append;
        parent::__construct($resource);
    }

    public function toArray($request) {
        \Log::info($this->start_date);
        $actionType = $this->{$this->actionable_type};
        return [
            'id' => $this->id,
            'area_id' => $this->area_id,
            'area' => $this->area->name,
            'name' => $this->name,
            'banner_type' => $this->actionable_type,
            'action_id' => $this->action_id,
            'actionObj' => [
                'id' => $this->action_id
                , 'name' => $actionType ? $actionType->name : null
            ],
            'section' => $this->section,
            'start_date' => $this->start_date ? Carbon::parse($this->start_date)->toDateString() : null,
            'end_date' => $this->end_date ? Carbon::parse($this->end_date)->toDateString() : null,
            'position' => $this->position,
            'status' => $this->status,
            'default' => $this->default,
            'image_en' => $this->imageEnUrl(),
            'image_ar' => $this->imageArUrl(),
            'created_at' => $this->created_at,
        ];
    }

}
