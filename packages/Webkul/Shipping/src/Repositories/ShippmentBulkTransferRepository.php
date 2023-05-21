<?php

namespace Webkul\Shipping\Repositories;
use App\Exceptions\ResponseErrorException;
use App\Jobs\BulkShippmentTransferRouter;
use App\Jobs\DispatchShippment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Webkul\Core\Eloquent\Repository;
use Webkul\Shipping\Models\Shippment;
use Webkul\Shipping\Models\ShippmentBulkTransfer;
use Webkul\Shipping\Models\ShippmentLogs;
use Webkul\Shipping\Models\ShippmentTransfer;

class ShippmentBulkTransferRepository extends Repository
{
    /**
     * Specify Model class name
     *
     * @return mixed
     */
    function model()
    {
        return 'Webkul\Shipping\Contracts\ShippmentBulkTransfer';
    }
    public function list($request) {

        $query = $this->newQuery()->with('bulkTransferItems');

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
            $query->where('id', 'LIKE', '%' . trim($request->filter) . '%');
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

    public function updateBulkTransferStatus(ShippmentBulkTransfer $shippmentTransfer , $data){
        $this->checkUpdateStatusAvailability($shippmentTransfer->status, $data['status']);
        $shippmentTransfer->update($data);
        $this->updateBulkStatus($shippmentTransfer , $data["status"]);
        BulkShippmentTransferRouter::dispatch($shippmentTransfer,$data["status"]);
        return $shippmentTransfer;
    }
    private function updateBulkStatus($transfer,$status){
        if($status==ShippmentBulkTransfer::STATUS_TRANSFERRED){
            Shippment::whereIn('id',$transfer->bulkTransferItems->pluck('shippment_id'))
            ->update(
                        [
                            'warehouse_id'=>$transfer->toWarehouse->id,
                            'area_id'=>$transfer->toWarehouse->area_id
                        ]
                    );
        }else if($status==ShippmentBulkTransfer::STATUS_CANCELLED){
            Shippment::whereIn('id',$transfer->bulkTransferItems->pluck('shippment_id'))
            ->update(
                        [
                            'current_status'=>Shippment::CURRENT_STATUS_PENDING_DISTRIBUTION
                        ]
                    );
        }
    }
    public function performOnBulkTransfer($transfer,$status){
        foreach($transfer->bulkTransferItems as $shipmentItem){
            if($status==ShippmentBulkTransfer::STATUS_TRANSFERRED){
                Event::dispatch('shippment.log',[$shipmentItem->shippment,ShippmentLogs::SHIPPMENT_DISTRIBUTION_DELIVERED]);
                if($shipmentItem->shippment->customer_address_id){
                    if($shipmentItem->shippment->first_trial_date < Carbon::now()){
                        $days = Carbon::now()->addDay()->format('Y-m-d');
                        $mins = Carbon::parse( $shipmentItem->shippment->first_trial_date)->format('H:i:s');
                        $shipmentItem->shippment->update(["first_trial_date"=>"$days $mins"]);
                    }
                    $shipmentItem->shippment->customerAddress->update(['area_id'=>$shipmentItem->shippment->area_id]);
                    //dispatch order for this shipment
                    DispatchShippment::dispatch($shipmentItem->shippment, 1);
                }else{
                    $shipmentItem->shippment->update(['current_status'=>Shippment::CURRENT_STATUS_PENDING_COLLECTING_CUSTOMER_INFO]);
                }

            }
            if($status==ShippmentBulkTransfer::STATUS_ON_THE_WAY){
                Event::dispatch('shippment.log',[$shipmentItem->shippment,ShippmentLogs::SHIPPMENT_DISTRIBUTION_ON_THE_WAY]);
            }
            if($status==ShippmentBulkTransfer::STATUS_CANCELLED){
                Event::dispatch('shippment.log',[$shipmentItem->shippment,ShippmentLogs::SHIPPMENT_DISTRIBUTION_CANCELLED]);
            }
        }
    }
    private function checkUpdateStatusAvailability($oldStatus , $newStatus){
        if (in_array($oldStatus, [ShippmentBulkTransfer::STATUS_CANCELLED, ShippmentBulkTransfer::STATUS_TRANSFERRED])) {
            throw new ResponseErrorException(406, 'عذراً لقد تم الانتهاء من هذا الطلب من قبل');
        }

        if($oldStatus==ShippmentBulkTransfer::STATUS_PENDING && $newStatus==ShippmentBulkTransfer::STATUS_TRANSFERRED){
            throw new ResponseErrorException(406, 'خطأ في ترتيب الحالات');
        }

        if($oldStatus==ShippmentBulkTransfer::STATUS_ON_THE_WAY && $newStatus==ShippmentBulkTransfer::STATUS_PENDING){
            throw new ResponseErrorException(406, 'بالفعل في الطريق');
        }
    }


    public function createBulkTransfer($data,$transferItems){
        $transfer = $this->create(["admin_id"=>auth()->id(),"from_warehouse_id"=>$data["from_warehouse"] , "to_warehouse_id" => $data['to_warehouse']]);
        $transfer->bulkTransferItems()->createMany($transferItems);
    }
}