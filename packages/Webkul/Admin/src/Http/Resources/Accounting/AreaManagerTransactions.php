<?php

namespace Webkul\Admin\Http\Resources\Accounting;

use App\Http\Resources\CustomResourceCollection;
use Carbon\Carbon;

class AreaManagerTransactions extends CustomResourceCollection
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
                "amount" => $transaction->amount,
                "image_url" => $transaction->image_url,
                "area_manager_current_wallet" => $transaction->areaManager->wallet,
                "area_manager_name" => $transaction->areaManager->admin->name,
                "area_name" => $transaction->area->name,
                "accountant" => $transaction->accountant ? $transaction->accountant->name : null,
                'transaction_date' => $transaction->created_at,
                'status' => $transaction->status,
            ];
        });
    }
}
