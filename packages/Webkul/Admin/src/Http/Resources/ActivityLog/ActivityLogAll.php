<?php

namespace Webkul\Admin\Http\Resources\ActivityLog;

use App\Http\Resources\CustomResourceCollection;

class ActivityLogAll extends CustomResourceCollection
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Support\Collection
     */
    public function toArray($request)
    {
        return  $this->collection->map(function ($log) {
            return [
                'id'            => $log->id,
                'text'           => $log->handleLogText(),
                'causer'            => $log->causer,
            ];
        });
    }

}