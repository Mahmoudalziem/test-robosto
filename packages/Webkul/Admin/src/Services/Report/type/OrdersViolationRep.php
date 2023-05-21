<?php

namespace Webkul\Admin\Services\Report\type;

use Webkul\Admin\Services\Report\ExportReprotService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Webkul\Admin\Exports\Reports\ExportReport;
use \Carbon\Carbon;

class OrdersViolationRep {

    public $name;
    protected $data;

    public function __construct(array $data) {
        $this->name = "orders-violation";
        $this->data = $data;
    }

    public function getMappedQuery() {

        $lang = $this->data['lang'];
        $dateFrom = $this->data['date_from'] ?? null;
        $dateTo = $this->data['date_to'] ?? null;
        $areaId = $this->data['area'] ?? null;

        if ($dateFrom && $dateTo) {
            $dateRange = " AND orders.created_at >= '$dateFrom 00:00:00' and orders.created_at <= '$dateTo 23:59:59' ";
        } else {

            $dateFrom = Carbon::today()->subDays(30)->toDateString();
            $dateTo = Carbon::today()->toDateString();
            $dateRange = " AND orders.created_at >= '$dateFrom 00:00:00' and orders.created_at <= '$dateTo 23:59:59' ";
        }


        if ($areaId) {
            $area = " AND orders.area_id= $areaId ";
        } else {
            $area = "  ";
        }

        $select = " SELECT 
                                        increment_id,
                                        type,
                                        AreaTrans.name area_name,
                                        collectors.name collector_name,
                                        drivers.name driver_name,
                                        admins.name admin_name,
                                        violation_type,
                                        violation_note,
                                        orders.created_at createdAt
                                    FROM orders 
                                        INNER JOIN order_violations OV ON orders.id = OV.order_id
                                        LEFT JOIN collectors ON OV.collector_id = collectors.id
                                        LEFT JOIN drivers ON OV.driver_id = drivers.id
                                        INNER JOIN admins ON OV.admin_id = admins.id
                                        INNER JOIN area_translations AreaTrans ON orders.area_id = AreaTrans.area_id
                                        WHERE orders.status='delivered'
                                        $dateRange
                                        $area
                                        AND AreaTrans.locale = '$lang'    
                        ";

        $select = preg_replace("/\r|\n/", "", $select);

        $query = collect(DB::select(DB::raw($select)));

        $counter = 1;
        $mappedQuery = $query->map(
                function ($item) use (&$counter) {
                    $data['#'] = $counter++;
                    $data['increment_id'] = $item->increment_id;
                    $data['area_name'] = $item->area_name;
                    $data['type'] = $item->type;
                    if ($item->type == 'collector') {
                        $data['collector_name'] = $item->collector_name;
                    } else {
                        $data['driver_name'] = $item->driver_name;
                    }

                    $data['admin_name'] = $item->admin_name;
                    $data['violation_type'] = $item->violation_type ? '( ' . (string) $item->violation_type . ' )' : '-';
                    $data['violation_note'] = $item->violation_note ? '( ' . (string) $item->violation_note . ' )' : '-';
                    $data['orderDate'] = Carbon::parse($item->createdAt)->toDateString();

                    return $data;
                },
                $query
        );
        return $mappedQuery;
    }

    public function getHeaddings() {
        return ['#', 'Order ID', 'Area', 'Type', 'Collector/Driver', 'Admin', 'Violation Type', 'Violation Note', 'Order Date'];
    }

    public function getName() {
        return $this->name;
    }

    public function download() {
        return Excel::download(new ExportReport($this->getMappedQuery(), $this->getHeaddings()), $this->getName() . '.xlsx');
    }

}
