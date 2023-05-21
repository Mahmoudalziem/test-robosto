<?php

namespace Webkul\Shipping\Repositories;
use App\Exceptions\ResponseErrorException;
use App\Jobs\DispatchShippment;
use Illuminate\Support\Facades\Event;
use Webkul\Core\Eloquent\Repository;
use Webkul\Shipping\Models\ShippmentLogs;
use Webkul\Shipping\Models\ShippmentTransfer;

class ShippmentTransferRepository extends Repository
{
    /**
     * Specify Model class name
     *
     * @return mixed
     */
    function model()
    {
        return 'Webkul\Shipping\Contracts\ShippmentTransfer';
    }
    public function list($request) {

        $query = $this->newQuery();

        // handle sort option
        if ($request->has('sort') && !empty($request->sort)) {

            // handle multisort
            $sorts = explode(',', $request->sort);
            foreach ($sorts as $sort) {
                list($sortCol, $sortDir) = explode('|', $sort);
                $query = $query->orderBy($sortCol, $sortDir);
            }
        } else {
            $query = $query->orderBy('id', 'desc');
        }

        if ($request->exists('status') && !empty($request['status'])) {
            $query->where('status',$request['status']);
        }

        if ($request->exists('src_warehouse') && !empty($request['src_warehouse'])) {
            $query->where('from_warehouse_id',$request['src_warehouse']);
        }

        if ($request->exists('to_warehouse') && !empty($request['to_warehouse'])) {
            $query->where('to_warehouse_id',$request['to_warehouse']);
        }
        if ($request->exists('filter') && !empty($request['filter'])) {
            $query->where('shippment_id', 'LIKE', '%' . trim($request->filter) . '%');
        }


        $perPage = $request->has('per_page') ? (int) $request->per_page : null;
        $pagination = $query->paginate($perPage);
        $pagination->appends([
            'sort' => $request->sort,
            'filter' => $request->filter,
            'per_page' => $request->per_page
        ]);

        return $pagination;
    }

    public function updateTransferStatus(ShippmentTransfer $shippmentTransfer , $data){
        $this->checkUpdateStatusAvailability($shippmentTransfer->status, $data['status']);
        $transfer = $shippmentTransfer->update($data);
        if($data['status']==ShippmentTransfer::STATUS_TRANSFERRED){
            $toWarehouse = $shippmentTransfer->toWarehouse;
            $shippmentTransfer->shippment()->update(['warehouse_id'=>$toWarehouse->id,'area_id'=>$toWarehouse->area_id]);
            Event::dispatch('shippment.log',[$shippmentTransfer->shippment,ShippmentLogs::SHIPPMENT_TRANSFERED]);
            Event::dispatch('shippment.log',[$shippmentTransfer->shippment,ShippmentLogs::SHIPPMENT_DISPATCHING]);
            DispatchShippment::dispatch($shippmentTransfer->shippment , 1);
        }else{
            Event::dispatch('shippment.log',[$shippmentTransfer->shippment,ShippmentLogs::SHIPPMENT_TRANSFER_ON_THE_WAY]);
        }
        return $transfer;
    }
    private function checkUpdateStatusAvailability($oldStatus , $newStatus){
        if (in_array($oldStatus, [ShippmentTransfer::STATUS_CANCELLED, ShippmentTransfer::STATUS_TRANSFERRED])) {
            throw new ResponseErrorException(406, 'عذراً لقد تم الانتهاء من هذا الطلب من قبل');
        }

        if($oldStatus==ShippmentTransfer::STATUS_PENDING && $newStatus==ShippmentTransfer::STATUS_TRANSFERRED){
            throw new ResponseErrorException(406, 'خطأ في ترتيب الحالات');
        }

        if($oldStatus==ShippmentTransfer::STATUS_ON_THE_WAY && $newStatus==ShippmentTransfer::STATUS_PENDING){
            throw new ResponseErrorException(406, 'بالفعل في الطريق');
        }
    }
}