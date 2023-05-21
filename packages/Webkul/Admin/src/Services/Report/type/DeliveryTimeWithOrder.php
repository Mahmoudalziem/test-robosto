<?php

namespace Webkul\Admin\Services\Report\type;

use Webkul\Admin\Services\Report\ExportReprotService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Webkul\Admin\Exports\Reports\ExportReport;

class DeliveryTimeWithOrder {

    public $name;
    protected $data;

    public function __construct(array $data) {
        $this->name = "delivery-time-with-order";
        $this->data = $data;
    }

    public function getMappedQuery() {

        $lang = $this->data['lang'];
        $dateFrom = $this->data['date_from'] ?? null;
        $dateTo = $this->data['date_to'] ?? null;
        $areaId = $this->data['area'] ?? null;

        if ($dateFrom && $dateTo) {
            $dateRange1 = " and orders.created_at >= '$dateFrom 00:00:00' and orders.created_at <= '$dateTo 23:59:59'  ";
            $dateRange2 = " and O.created_at >= '$dateFrom 00:00:00' and O.created_at <= '$dateTo 23:59:59'  ";
        } else {
            $dateRange1 = "  ";
            $dateRange2 = "  ";
        }

        if ($areaId) {
            $area1 = " and orders.area_id= $areaId ";
            $area2 = " and O.area_id= $areaId ";
        } else {
            $area1 = "  ";
            $area2 = "  ";
        }


        $select = " SELECT count(orders.id) as 'orders_count' ,
                        (select name from drivers where id = orders.driver_id) as 'driver_name' ,
                        (SELECT sum(TIMESTAMPDIFF (SECOND,T1.log_time,T2.log_time)/60) as 'delivery_time_in_minutes' 
                        FROM order_logs_actual T1 
                        inner join order_logs_actual T2 on T1.order_id = T2.order_id 
                        where T1.log_type = 'order_driver_items_confirmed' 
                            and T2.log_type = 'order_driver_items_delivered' 
                            and T1.order_id in (select id from orders O where O.driver_id = orders.driver_id 
                                                                    $dateRange2 
                                                                    $area2
                                                                    and O.status = 'delivered' )
                                        ) as 'delivery_time' 
                    FROM orders   
                    where orders.status = 'delivered' 
                    $dateRange1
                    $area1
                    group by orders.driver_id ";

        $select = preg_replace("/\r|\n/", "", $select);

        $query = collect(DB::select(DB::raw($select)));
        
        $counter = 1;
        $mappedQuery = $query->map(
                function ($item) use (&$counter) {
                    $data['#'] = $counter++;
                    $data['orders_count'] = $item->orders_count;
                    $data['driver_name'] = $item->driver_name;
                    $data['delivery_time'] = $item->delivery_time;

                    return $data;
                }, $query
        );
        return $mappedQuery;
    }

    public function getHeaddings() {
        return ['#', 'Orders count', 'Driver name', 'Delivery time'];
    }

    public function getName() {
        return $this->name;
    }

    public function download() {
        return Excel::download(new ExportReport($this->getMappedQuery(), $this->getHeaddings()), $this->getName() . '.xlsx');
    }

}
