<?php

namespace Webkul\Admin\Services\Report\type;

use Webkul\Admin\Services\Report\ExportReprotService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Webkul\Admin\Exports\Reports\ExportReport;

class OrdersCountPerHourWithDeliveryTimeRep {

    public $name;
    protected $data;

    public function __construct(array $data) {
        $this->name = "orders-count-per-hour-with-delivery-time";
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
            $area = " and area_id= $areaId ";
        } else {
            $area = "  ";
        }

        $select = " SELECT
                    COUNT(id) AS 'number_of_orders',
                    SUM(timee) as 'total_delivery_time',
                    DATE_FORMAT(CONCAT(DATE(created_at), ' ', HOUR(created_at)),'%H') AS 'hours'
                    FROM (SELECT
                                    (SELECT TIMESTAMPDIFF(SECOND, O1.log_time, O2.log_time) / 60
                                    FROM order_logs_actual O1
                                    INNER JOIN order_logs_actual O2 ON O1.order_id = O2.order_id
                                    WHERE O1.log_type = 'order_placed'
                                    AND O2.log_type = 'order_driver_items_delivered'
                                    AND O1.order_id = MO.id LIMIT 1) AS timee,
                    created_at,
                    status,
                    id
                    FROM (SELECT distinct orders.id AS id, area_id,  orders.created_at,status
                                   FROM orders
                                   INNER JOIN order_items ON orders.id = order_items.order_id
                                   WHERE order_items.product_id not in (1544 , 1632) ) AS MO
                    WHERE status = 'delivered'
                    $area 
                    $dateRange 
                    ) T
                    GROUP BY DATE_FORMAT(CONCAT(DATE(created_at), ' ', HOUR(created_at)), '%H')
                    ORDER BY hours";

        $select = preg_replace("/\r|\n/", "", $select);
        
        $query = collect(DB::select(DB::raw($select)));
        $counter = 1;
        $mappedQuery = $query->map(
                function ($item) use (&$counter) {
                    $data['#'] = $counter++;
                    $data['number_of_orders'] = $item->number_of_orders;
                    $data['total_delivery_time'] = $item->total_delivery_time;
                    $data['hours'] = $item->hours;                    

                    return $data;
                }, $query
        );
        return $mappedQuery;
    }

    public function getHeaddings() {
        return ['#', 'number_of_orders', 'total_delivery_time', 'hours'];
    }

    public function getName() {
        return $this->name;
    }

    public function download() {
        return Excel::download(new ExportReport($this->getMappedQuery(), $this->getHeaddings()), $this->getName() . '.xlsx');
    }

}
