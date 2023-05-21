<?php

namespace Webkul\Admin\Http\Resources\Customer;

use App\Http\Resources\CustomResourceCollection;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class CustomerNotesAll extends CustomResourceCollection {

    /**
     * Transform the resource into an array.
     *
     * @param Request
     * @return Collection
     */
    public function toArray($request) {


        return $this->collection->map(function ($customerNote) {

            return [
                'id' => $customerNote->id,
                'text' => $customerNote->text,
                'admin' => $customerNote->admin->name,
                'customer' => $customerNote->customer->name,                
                'note_date' => Carbon::parse($customerNote->created_at)->format('d M Y h:i:s'),
            ];
        });
    }

}
