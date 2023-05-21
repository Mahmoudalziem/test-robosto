<?php

namespace Webkul\Admin\Http\Resources\ActivityLog;
use Illuminate\Http\Resources\Json\JsonResource;


class ActivityLogSingle extends JsonResource
{
    protected $append;
    public function __construct($resource,$append=null)
    {
        $this->append=$append;
        parent::__construct($resource);

    }

    public function toArray($request)
    {
        return [
            'id'            => $this->id,
            'text'           => $this->handleLogText(),
            'action_type'           => $this->action_type,
            'subject'            => $this->subject,
            'causer'            => $this->causer,
            'properties'            => $this->properties,
        ];

    }

}