<?php

namespace Webkul\Admin\Http\Resources\Customer;

use App\Http\Resources\CustomResourceCollection;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class CustomerWalletNotesAll extends CustomResourceCollection {

    /**
     * Transform the resource into an array.
     *
     * @param Request
     * @return Collection
     */
    public function toArray($request) {


        return $this->collection->map(function ($walletNote) {

            return [
                'id' => $walletNote->id,
                'text' => $walletNote->text,
                'amount' => $walletNote->amount,
                'wallet_before' => $walletNote->wallet_before,
                'type' => $walletNote->type,
                'admin' => $walletNote->admin->name,
                'order_id' => $walletNote->order_id,
                'reason' => $walletNote->reason?$walletNote->reason->reason:null,
                'date' => Carbon::parse($walletNote->created_at)->format('d M Y h:i:s'),
            ];
        });
    }

}
