<?php

namespace Webkul\Admin\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Webkul\Admin\Repositories\PurchaseOrder\PurchaseOrderRepository;

class POExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithEvents 
{
    private $counter = 1;
    
    protected $purchaseOrderRepository;
    protected $id;
    public function __construct(PurchaseOrderRepository $purchaseOrderRepository , $id)
    {
        $this->purchaseOrderRepository = $purchaseOrderRepository;
        $this->id = $id;
    }


    public function collection() {
        return $this->purchaseOrderRepository->getPOFullInfo($this->id);
    }

    public function headings(): array
    {
        return [
            '#', 'purchase_order_id', 'sku', 'product_id', 'qty bought', 'total buying price', 'qty sold' , 'total selling price' , 'adjusted_up' ,'adjusted_down' , 'creation date'
        ];   
    }

    public function map($poRow): array
    {

        return [
            $this->counter++,
            $poRow->purchase_order_id,
            $poRow->sku,
            "$poRow->product_id",
            "$poRow->qty",
            "$poRow->amount",
            "$poRow->sold_quantity",
            "$poRow->sold_price",
            "$poRow->adjusted_up",
            "$poRow->adjusted_down",
            Carbon::parse($poRow->created_at)->format('d-m-Y')
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
                $event->sheet->getStyle('A1:K1')->applyFromArray($styleArray);
            },
        ];
    }
}