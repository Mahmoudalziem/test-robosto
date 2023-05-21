<?php

namespace Webkul\Admin\Services\Report\type;

use Webkul\Admin\Services\Report\ExportReprotService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Webkul\Admin\Exports\Reports\ExportReport;
use Webkul\Driver\Models\Driver;
use Spatie\EventSourcing\StoredEvents\Models\EloquentStoredEvent;
use Webkul\Driver\Events\MoneyAdded;
use Webkul\Sales\Models\Order;
use Webkul\Shipping\Models\Shipper;
class AmountCollectedRep
{

    protected $name;
    protected $data;

    public function __construct(array $data)
    {
        $this->name = "amount-collected";
        $this->data = $data;
    }

    public function getMappedQuery() {
        $dateFrom = $this->data['date_from'] ?? null;
        $dateTo = $this->data['date_to'] ?? null;
        $areaId = $this->data['area'] ?? null;
        if($areaId){
            $drivers = Driver::where('area_id',$areaId)->with('area')->get();
        }else{
            $drivers = Driver::with('area')->get();
        }
        if(!$dateFrom && !$dateTo){
            $dateFrom = "";
            $dateTo="";
        }
        $shippers = Shipper::all();
        $orders = EloquentStoredEvent::query()->
            whereEventClass(MoneyAdded::class)->
            whereBetween('created_at', ["$dateFrom 00:00:00", "$dateTo 23:59:59"])->
            whereIn('event_properties->driverId', $drivers->pluck('id'))->
            latest()->get();


        $shippedOrders = Order::whereIn('id', $orders->pluck('event_properties.orderId'))->with('shippment')->get();
        $ships = [];
        foreach($shippedOrders as $order){
            if(isset($order->shippment_id)){
                $ships[$order->increment_id] = ["id"=>$order->shippment->id , "shipper"=>$order->shippment->shipper_id];
            }
        }
        $counter = 1;
        $mappedQuery = $orders->map(
            function ($item) use (&$counter, $drivers , $ships ,$shippers) {
            $data['#'] = $counter++;
            $data['order_id'] = $item->event_properties['orderIncrementId'] ?? null;
            $data['driver_name'] = '( ' . $drivers->where('id', $item->event_properties['driverId'])->first()->name . ' )';
            $data['amount'] =$item->event_properties['amount']."";
            $data['area'] = '( ' . $drivers->where('id', $item->event_properties['driverId'])->first()->area->name . ' )';
            $data['date'] = $item->created_at ?? null;
            $data['shipment_id']= isset($ships[$item->event_properties['orderIncrementId']]) ? $ships[$item->event_properties['orderIncrementId']]['id']:'not a shipment';
            $data['shipper'] = isset($ships[$item->event_properties['orderIncrementId']]) ? $shippers->where('id',$ships[$item->event_properties['orderIncrementId']]['shipper'])->first()->name:'not a shipment';
            return $data;
        }, $orders
        );
        return $mappedQuery;
    }

    public function getHeaddings()
    {
        return ['#', 'order_id', 'driver_name', 'amount', 'area', 'date','shipment_id','shipper'];
    }

    public function getName()
    {
        return $this->name;
    }

    public function download()
    {
        return Excel::download(new ExportReport($this->getMappedQuery(), $this->getHeaddings()), $this->getName() . '.xlsx');
    }


}
