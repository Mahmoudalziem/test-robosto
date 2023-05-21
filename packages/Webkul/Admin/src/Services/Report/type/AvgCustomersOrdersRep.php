<?php

namespace Webkul\Admin\Services\Report\type;

use Webkul\Admin\Services\Report\ExportReprotService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Webkul\Admin\Exports\Reports\ExportReport;

class AvgCustomersOrdersRep {

    public $name;
    protected $data;

    public function __construct(array $data) {
        $this->name = __("admin::report.avg-customers-orders");
        $this->data = $data;
    }

    public function getMappedQuery() {

        $dateFrom = $this->data['date_from'] ?? null;
        $dateTo = $this->data['date_to'] ?? null;
        $areaId = $this->data['area'] ?? null;

        if ($dateFrom && $dateTo) {
            $dateRange = " WHERE C.created_at >= '$dateFrom 00:00:00' and C.created_at <= '$dateTo 23:59:59' ";
        } else {
            $dateRange = "  ";
        }

        if ($areaId) {
            $area = " WHERE orders.area_id = $areaId ";
        } else {
            $area = " WHERE 1=1 ";
        }
        $notShippment = " and orders.shippment_id is null ";
        $delivered = " and orders.status = 'delivered' ";
        $select = "SELECT C.name, phone, avg_days_purchases, no_of_Orders, days_from_last_order , total_sum FROM customers C
                    INNER JOIN
                        (SELECT 
                                customer_id,
                                TIMESTAMPDIFF(DAY, MIN(created_at), MAX(created_at)) / (COUNT(customer_id) - 1) AS 'avg_days_purchases',
                                COUNT(customer_id) AS 'no_of_Orders',
                                DATEDIFF(NOW(), MAX(created_at)) AS 'days_from_last_order',
                                SUM(final_total) as 'total_sum'
                            FROM orders
                            {$area}
                            {$notShippment}
                            {$delivered}
                            GROUP BY customer_id
                        ) AVO 
                    ON C.id = AVO.customer_id
                    {$dateRange}";

        $select = preg_replace("/\r|\n/", "", $select);

        $query = collect(DB::select(DB::raw($select)));
        $counter = 1;
        $mappedQuery = $query->map(function ($item) use (&$counter) {
                    $data['#'] = $counter++;
                    $data['name'] = $item->name;
                    $data['phone'] =   $item->phone;
                    $data['avg_days_purchases'] =   $item->avg_days_purchases;
                    $data['no_of_Orders'] =   $item->no_of_Orders;
                    $data['total_sum'] =   $item->total_sum;
                    $data['days_from_last_order'] =   $item->days_from_last_order;
                    return $data;
                }
        );
        return $mappedQuery;
    }

    public function getHeaddings() {
        return ['#', 'Customer Name', 'Customers Phone', 'Average Days Purchases', 'Number Of Orders', 'Total Orders Value','Days From Last Order'];
    }

    public function getName() {
        return $this->name;
    }

    public function download() {
        return Excel::download(new ExportReport($this->getMappedQuery(), $this->getHeaddings()), $this->getName() . '.xlsx');
    }

}
