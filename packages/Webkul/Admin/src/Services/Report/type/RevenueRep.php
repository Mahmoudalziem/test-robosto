<?php

namespace Webkul\Admin\Services\Report\type;

use Webkul\Admin\Services\Report\ExportReprotService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Webkul\Admin\Exports\Reports\ExportReport;

class RevenueRep {

    public $name;
    protected $data;

    public function __construct(array $data) {
        $this->name = "revenue";
        $this->data = $data;
    }

    public function getMappedQuery() {

        $lang = $this->data['lang'];
        $dateFrom = $this->data['date_from'] ?? null;
        $dateTo = $this->data['date_to'] ?? null;
        $areaId = $this->data['area'] ?? null;        
        
        if ($dateFrom && $dateTo) {
            $dateRange = " and created_at >= '$dateFrom 00:00:00' and created_at <= '$dateTo 23:59:59' ";
        } else {
            $dateRange = "  ";
        }
        
        if ($areaId) {
            $area = " and orders.area_id= $areaId ";
        } else {
            $area = "  ";
        }          

        $select = " SELECT SUM(final_total) total
                    FROM orders
                    WHERE status = 'delivered'
                    $dateRange
                    $area ";

        $select = preg_replace("/\r|\n/", "", $select);

        $query = collect(DB::select(DB::raw($select)));
        $counter = 1;
        $mappedQuery = $query->map(
                function ($item) use (&$counter) {
                    $data['#'] = $counter++;
                    $data['total'] = $item->total;

                    return $data;
                }, $query
        );
        return $mappedQuery;
    }

    public function getHeaddings() {
        return ['#', 'total' ];
    }

    public function getName() {
        return $this->name;
    }

    public function download() {
        return Excel::download(new ExportReport($this->getMappedQuery(), $this->getHeaddings()), $this->getName() . '.xlsx');
    }

}
