<?php

namespace Webkul\Admin\Http\Resources\Accounting;

use Carbon\Carbon;
use App\Http\Resources\CustomResourceCollection;
use Webkul\Driver\Models\DriverTransactionRequest;

class DriverTransactions extends CustomResourceCollection
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
                "amount" => $transaction->amount,
                "driver_current_wallet" => $transaction->current_wallet,
                "driver_name" => $transaction->driver->name,
                "warehouse_name" => $transaction->warehouse->name,
                "area_name" => $transaction->area->name,
                "area_manager" => $transaction->admin ? $transaction->admin->name : null,
                'transaction_date' => $transaction->created_at,
                'status' => $transaction->status,
            ];
        });
    }
}
