<?php

namespace Webkul\Admin\Http\Resources\Sales;

use App\Http\Resources\CustomResourceCollection;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class OrderNotesAll extends CustomResourceCollection {

    /**
     * Transform the resource into an array.
     *
     * @param Request
     * @return Collection
     */
    public function toArray($request) {


        return $this->collection->map(function ($orderNote) {

            return [
                'id' => $orderNote->id,
                'text' => $orderNote->text,
                'admin' => $orderNote->admin->name,
                'note_date' => Carbon::parse($orderNote->created_at)->format('d M Y h:i:s'),
            ];
        });
    }

}
