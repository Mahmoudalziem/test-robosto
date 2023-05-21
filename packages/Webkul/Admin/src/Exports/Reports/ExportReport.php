<?php

namespace Webkul\Admin\Exports\Reports;

use Carbon\Carbon;
use Webkul\Customer\Models\Customer;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\FromQuery;
use \Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Webkul\Admin\Repositories\Customer\AdminCustomerRepository;

class ExportReport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize, WithEvents {

    private $counter = 1;
    protected $query;
    protected $headings;

    /**
     * @param AdminCustomerRepository $customerRepository
     */
    public function __construct($query, $headings) {
        $this->query = $query;
        $this->headings = $headings;
    }

    public function collection(): \Illuminate\Support\Collection {
        return $this->query;
    }

    public function headings(): array {
        return $this->headings;
    }

    public function styles(Worksheet $sheet) {
        return [
            'C' => [
                'font' => ['name' => 'arial'],
                'alignment' => ['horizontal' => 'center'],
            ],
        ];
    }

    public function registerEvents(): array {
        $styleArray = [
            'font' => [
                'name' => 'arial',
                'bold' => true,
                'color' => [
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
            AfterSheet::class => function (AfterSheet $event) use ($styleArray) {
                $event->sheet->getStyle('A1:G1')->applyFromArray($styleArray);
            },
        ];
    }

}
