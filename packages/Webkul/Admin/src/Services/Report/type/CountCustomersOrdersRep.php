<?php

namespace Webkul\Admin\Services\Report\type;

use Webkul\Admin\Services\Report\ExportReprotService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Webkul\Admin\Exports\Reports\ExportReport;

class CountCustomersOrdersRep {

    public $name;
    protected $data;

    public function __construct(array $data) {
        $this->name = __("admin::report.count-customers-orders");
        $this->data = $data;
    }

    public function getMappedQuery() {

        $lang = $this->data['lang'];

        $dateFrom = $this->data['date_from'] ?? null;
        $dateTo = $this->data['date_to'] ?? null;
        $areaId = $this->data['area'] ?? null;

        if ($dateFrom && $dateTo) {
            $dateRange = " WHERE CO.created_at >= '$dateFrom 00:00:00' and CO.created_at <= '$dateTo 23:59:59' ";
        } else {
            $dateRange = "  ";
        }

        if ($areaId) {
            $area = " AND areas.id = $areaId ";
        } else {
            $area = "  ";
        }

        $select = "SELECT ART.name AS area_name, active_customers, loyal_customers FROM areas
                    LEFT JOIN
                        (SELECT sub.area_id, COUNT(sub.count_customers) AS 'active_customers'
                            FROM 
                                (SELECT area_id, COUNT(customer_id) AS 'count_customers' FROM orders AS CO {$dateRange} GROUP BY CO.customer_id , CO.area_id ) AS sub
                            GROUP BY sub.area_id
                        ) AS ACO 
                    ON areas.id = ACO.area_id
                    LEFT JOIN
                        (SELECT sub.area_id, COUNT(sub.count_customers) AS 'loyal_customers'
                            FROM
                                (SELECT area_id, COUNT(customer_id) AS 'count_customers' FROM orders AS CO {$dateRange} GROUP BY CO.customer_id , CO.area_id HAVING count_customers > 2) AS sub
                            GROUP BY sub.area_id
                        ) AS ALO 
                    ON areas.id = ALO.area_id
                    INNER JOIN
                        area_translations ART ON ART.area_id = areas.id
                    WHERE
                        ART.locale = '{$lang}' {$area}";

        $select = preg_replace("/\r|\n/", "", $select);

        $query = collect(DB::select(DB::raw($select)));
        $counter = 1;
        $mappedQuery = $query->map(function ($item) use (&$counter) {
                    $data['#'] = $counter++;
                    $data['area_name'] = $item->area_name;
                    $data['active_customers'] =   $item->active_customers;
                    $data['loyal_customers'] =   $item->loyal_customers;
                    return $data;
                }
        );
        return $mappedQuery;
    }

    public function getHeaddings() {
        return ['#', 'Area Name', 'Active Customers (With 1 order at least)', 'Loyal Customers (With 3 order at least)'];
    }

    public function getName() {
        return $this->name;
    }

    public function download() {
        return Excel::download(new ExportReport($this->getMappedQuery(), $this->getHeaddings()), $this->getName() . '.xlsx');
    }

}
