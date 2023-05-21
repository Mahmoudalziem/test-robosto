<?php

namespace Webkul\Shipping\Exports;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Webkul\Shipping\Repositories\ShippmentRepository;

class ShippmentsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithEvents
{
    private $counter = 1;

    protected $shippmentRepository;
    protected $guardType;

    public function __construct(ShippmentRepository $shippmentRepository ,$guardType )
    {
        $this->shippmentRepository = $shippmentRepository;
        $this->guardType=$guardType;
    }

    public function query()
    {
        return $this->shippmentRepository->list(request(),$this->guardType,true);
    }

    public function headings(): array
    {
        return [
            '#','Current Store','Shipper Name','Merchant','Address', 'Shipping Number', 'Name', 'Phone', 'Status' , 'Note' , 'Description' ,'Price','Items Count','Created At' ,'Pickup date','1st attempt date','1st attempt reply','2nd attempt date','2nd attempt reply','3rd attempt date','3rd attempt reply','4th attempt date','4th attempt reply','5th attempt date','5th attempt reply','Last action date' ,'Last action reply' , 'Last Cancellation Reason' , 'RTO Date' , 'RTS' , 'Total Trials'
        ];
    }

    

    public function map($shippment): array
    {
        Log::info('shipment :'.$shippment->id);
        $pickupOrder=null;
        $trials=[];
        foreach($shippment->orders as $order){
            if(!$order->customer_id){
                $pickupOrder = $order;
            }else{
                array_push($trials,$order);
            }
        }
        $pickupDate ='';
        $trial_1_date='';
        $trial_1_reply='';
        $trial_2_date='';
        $trial_2_reply='';
        $trial_3_date='';
        $trial_3_reply='';
        $trial_4_date='';
        $trial_4_reply='';
        $trial_5_date='';
        $trial_5_reply='';
        $last_action_date='';
        $last_action_reply='';
        $last_cancellation_reason = '';
        $rto_date = '';
        if($pickupOrder){
            $pickupDate= Carbon::parse($pickupOrder->delivered_at)->format('d-m-Y h:i A');
        }
        foreach($trials as $key => $trial){
            if($key+1==count($trials)){
                $last_action_date=Carbon::parse($trial->created_at)->format('d-m-Y h:i A');
                if($trial->status=='cancelled'){
                    $last_action_date=Carbon::parse($trial->cancelled_at)->format('d-m-Y h:i A');
                }
            }
            $v = $key+1;
            ${'trial_'. $v .'_date'} = Carbon::parse($trial->created_at)->format('d-m-Y h:i A');
            if($trial->status=='scheduled'){
                $trial->status = $trial->status . '-' .$trial->scheduled_at;
            }
            if($trial->status=='cancelled'){
                $reason = '';
                if(isset($trial->cancelReason)){
                    $reason = isset($trial->cancelReason->reason) ? $trial->cancelReason->reason : '';
                }
                $trial->status = $trial->status . '-' .$trial->cancelled_reason . ' '.$reason;
                $last_cancellation_reason = $trial->cancelled_reason . ' '.$reason;
            }
            if($trial->status=='delivered'){
                $last_action_date=Carbon::parse($trial->delivered_at)->format('d-m-Y h:i A');
            }
            if($trial->status=='cancelled'){
                $last_action_date=Carbon::parse($trial->cancelled_at)->format('d-m-Y h:i A');
            }
            ${'trial_'. $v .'_reply'} = $trial->status;
            $last_action_reply = $trial->status;
            if($trial->status=='scheduled' && $v==1){
                ${'trial_'. $v .'_date'} = '';
                ${'trial_'. $v .'_reply'} = 'scheduled';
            }
        }
        if($shippment->status=='failed'){
            if($shippment->current_status=='failed_picking_up_items'){
                $shippment->status .= '-'.'failed to pickup';
                $pickupDate='';
            }else if($shippment->current_status=='returned_to_vendor'){
                $shippment->status .= '-'.'RTO';
                $rto_date = $shippment->rto_at;
            }
            else{
                $shippment->status .= '-'.'failed to deliver';
            }
        }
        if($shippment->status=='pending'){
            if($shippment->current_status=='pending_transfer'){
                $shippment->status .= '-'.'transfer';
            }else if($shippment->current_status=='pending_collecting_customer_info'){
                $shippment->status .= '-'.'collecting customer information';
            }else if($shippment->current_status=='pending_picking_up_items'){
                $shippment->status .= '-'.'picking up items';
            }
        }
        if($this->guardType=='admin'||$shippment->shipper->id==4){
            $warehouse = $shippment->warehouse->name;
        }else{
            $warehouse = 'Store '.$shippment->warehouse->id;
        }
        return [
            $this->counter++,
            $warehouse,
            $shippment->shipper->name,
            $shippment->merchant,
            $shippment->shippingAddress->address,
            $shippment->shipping_number,
            $shippment->shippingAddress->name,
            $shippment->shippingAddress->phone,
            $shippment->status,
            $shippment->note,
            $shippment->description,
            $shippment->final_total,
            $shippment->items_count,
            Carbon::parse($shippment->created_at)->format('d-m-Y'),
            $pickupDate,
            $trial_1_date,
            $trial_1_reply,
            $trial_2_date,
            $trial_2_reply,
            $trial_3_date,
            $trial_3_reply,
            $trial_4_date,
            $trial_4_reply,
            $trial_5_date,
            $trial_5_reply,
            $last_action_date,
            $last_action_reply,
            $last_cancellation_reason,
            $rto_date,
            $shippment->is_rts?'YES':'NO',
            count($trials)
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            'C'  => [
                'font' => ['name' => 'arial'],
                'alignment' =>  ['horizontal' => 'center'],
            ],
        ];
    }

    public function registerEvents(): array
    {
        $styleArray = [
            'font' => [
                'name'  =>  'arial',
                'bold' => true,
                'color' =>  [
                    'argb' => 'FFFFFF',
                ]
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_GRADIENT_LINEAR,
                'startColor' => [
                    'argb' => '3F446B',
                ]
            ],
        ];

        return [
            AfterSheet::class    => function (AfterSheet $event) use ($styleArray) {
                $event->sheet->getStyle('A1:G1')->applyFromArray($styleArray);
            },
        ];
    }
}