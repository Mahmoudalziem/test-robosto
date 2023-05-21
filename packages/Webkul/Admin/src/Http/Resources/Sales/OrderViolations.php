<?php

namespace Webkul\Admin\Http\Resources\Sales;

use App\Http\Resources\CustomResourceCollection;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class OrderViolations extends CustomResourceCollection {

    /**
     * Transform the resource into an array.
     *
     * @param Request
     * @return Collection
     */
    public function toArray($request) {


        return $this->collection->map(function ($violation) {

            return [
                'id' => $violation->id,
                'type' => $violation->type,
                'violation_type' => $violation->violation_type,
                'violation_note' => $violation->violation_note,
                // 'order_id' => $violation->order ? $violation->order->id : null,
                'order_id' => $violation->order ? $violation->order->increment_id : null,
                'driver' => $violation->driver ? $violation->driver->name : null,
                'collector' => $violation->collector ? $violation->collector->name : null,
                'admin' => $violation->admin ? $violation->admin->name : null,
                'date' => Carbon::parse($violation->created_at)->format('d M Y h:i:s'),
            ];
        });
    }

}
