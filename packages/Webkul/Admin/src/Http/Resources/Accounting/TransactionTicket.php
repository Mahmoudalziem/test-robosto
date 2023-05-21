<?php

namespace Webkul\Admin\Http\Resources\Accounting;

use App\Http\Resources\CustomResourceCollection;
use Carbon\Carbon;

class TransactionTicket extends CustomResourceCollection
{

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Support\Collection
     */
    public function toArray($request)
    {
        return $this->collection->map(function ($transaction) {
            return [
                "id" => $transaction->id,
                'transaction_id' => $transaction->transaction_id,
                "sender" => $transaction->sender->name,
                "note" => $transaction->note,
                'date' => Carbon::parse($transaction->created_at)->format('d M Y H:i:s a'),
            ];
        });
    }
}
