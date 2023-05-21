<?php

namespace Webkul\Admin\Exports;

use Carbon\Carbon;
use Webkul\Customer\Models\Customer;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Webkul\Admin\Repositories\Customer\AdminCustomerRepository;

class CustomersExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithEvents
{
    private $counter = 1;

    protected $customerRepository;

    /**
     * @param AdminCustomerRepository $customerRepository
     */
    public function __construct(AdminCustomerRepository $customerRepository)
    {
        $this->customerRepository = $customerRepository;
    }

    public function query()
    {
        return $this->customerRepository->list(request(), true);
    }

    public function headings(): array
    {
        return [
            '#', 'Source', 'Name', 'Phone', 'Email', 'Gender','Wallet', 'Joind_at'
        ];
    }

    /**
     * @var Customer $customer
     */
    public function map($customer): array
    {

        return [
            $this->counter++,
            $customer->channel_id == 1 ? 'CALL_CENTER' : 'MOBILE_APP',
            $customer->name,
            $customer->phone,
            $customer->email,
            $customer->gender == 0 ? 'Male' : 'Female',
            $customer->wallet,
            Carbon::parse($customer->created_at)->format('d-m-Y')
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