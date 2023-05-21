<?php

namespace Webkul\Admin\Http\Resources\Driver;


use App\Http\Resources\CustomResourceCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Webkul\Sales\Models\OrderDriverDispatch;
use Webkul\Sales\Models\OrderLogsActual;
use Webkul\Sales\Models\OrderLogsEstimated;

class LogLoginAll extends CustomResourceCollection
{
    /**
     * Transform the resource into an array.
     *
     * @param Request
     * @return Collection
     */
    public function toArray($request)
    {


        return $this->collection->map(function ($log) {
            return [
                'id' => $log->id,
                'action' => $log->action,
                'created_at' => $log->created_at,
            ];
        });
    }

}