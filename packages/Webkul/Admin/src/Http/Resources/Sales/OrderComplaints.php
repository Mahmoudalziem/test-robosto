<?php

namespace Webkul\Admin\Http\Resources\Sales;

use App\Http\Resources\CustomResourceCollection;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class OrderComplaints extends CustomResourceCollection {

    /**
     * Transform the resource into an array.
     *
     * @param Request
     * @return Collection
     */
    public function toArray($request) {


        return $this->collection->map(function ($complaint) {

            return [
                'id' => $complaint->id,
                'text' => $complaint->text,
                'complaint_date' => Carbon::parse($complaint->created_at)->format('d M Y h:i:s'),
            ];
        });
    }

}
