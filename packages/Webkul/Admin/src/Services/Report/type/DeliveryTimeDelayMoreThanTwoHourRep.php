<?php

namespace Webkul\Admin\Services\Report\type;

use Webkul\Admin\Services\Report\ExportReprotService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Webkul\Admin\Exports\Reports\ExportReport;

class DeliveryTimeDelayMoreThanTwoHourRep {

    public $name;
    protected $data;

    public function __construct(array $data) {
        $this->name = "delivery-time-delay-more-than-two-hour";
        $this->data = $data;
    }

    public function getMappedQuery() {

        $lang = $this->data['lang'];
        $dateFrom = $this->data['date_from'] ?? null;
        $dateTo = $this->data['date_to'] ?? null;
        $areaId = $this->data['area'] ?? null;

        if ($dateFrom && $dateTo) {
            $dateRange = " and orders.created_at >= '$dateFrom 00:00:00' and orders.created_at <= '$dateTo 23:59:59' ";
        } else {
            $dateRange = "  ";
        }
        
        if ($areaId) {
            $area = " and orders.area_id= $areaId ";
        } else {
            $area = "  ";
        }        
        $notScheduled = " and orders.scheduled_at is null ";
        $notShippment = " and orders.shippment_id is null ";
        $select = " SELECT
                        name as 'area_name',
                        total_time  AS 'total_delivery_minutes',
                        orders_count AS 'number_of_orders',
                        orders_avg AS 'average_delivery_time'
                    FROM
                        (SELECT orders.area_id,
                                SUM(total) AS 'total_time',
                                COUNT(total) AS 'orders_count',
                                AVG(total) AS 'orders_avg'
                        FROM (SELECT
                                O1.order_id,
                                O1.log_time AS 'order_placed',
                                O2.log_time AS 'order_delivered',
                                ( TIMESTAMPDIFF(SECOND, O1.log_time, O2.log_time) ) / 60 AS 'total'
                        FROM order_logs_actual O1
                        INNER JOIN order_logs_actual O2 ON O1.order_id = O2.order_id
                        WHERE O1.log_type = 'order_placed'
                        AND O2.log_type = 'order_driver_items_delivered') OL
                        INNER JOIN ( SELECT distinct orders.id AS id, area_id, orders.created_at,status,orders.scheduled_at,orders.shippment_id
                                   FROM orders
                                   INNER JOIN order_items ON orders.id = order_items.order_id
                                   WHERE order_items.product_id not in (1544 , 1632) ) AS orders
                        ON OL.order_id = orders.id
                        WHERE orders.status = 'delivered'
                            $notShippment
                            $notScheduled
                            $dateRange
                            $area
                            and OL.total > 90
                        GROUP BY orders.area_id
                        ) TT        
                        INNER JOIN area_translations 
                        ON area_translations.area_id = TT.area_id
                        WHERE locale =  '" . $lang . "' ";

        $select = preg_replace("/\r|\n/", "", $select);

        $query = collect(DB::select(DB::raw($select)));
        $counter = 1;
        $mappedQuery = $query->map(
                function ($item) use (&$counter) {
                    $data['#'] = $counter++;
                    $data['area_name'] = $item->area_name;
                    $data['total_delivery_minutes'] = $item->total_delivery_minutes;
                    $data['number_of_orders'] = $item->number_of_orders;
                    $data['average_delivery_time'] = $item->average_delivery_time;

                    return $data;
                }, $query
        );
        return $mappedQuery;
    }

    public function getHeaddings() {
        return ['#', 'Area', 'Total delivery minutes', 'Number of_orders', 'Average delivery time'];
    }

    public function getName() {
        return $this->name;
    }

    public function download() {
        return Excel::download(new ExportReport($this->getMappedQuery(), $this->getHeaddings()), $this->getName() . '.xlsx');
    }

}
