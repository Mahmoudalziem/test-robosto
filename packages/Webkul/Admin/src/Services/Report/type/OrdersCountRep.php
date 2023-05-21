<?php

namespace Webkul\Admin\Services\Report\type;

use Webkul\Admin\Services\Report\ExportReprotService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Webkul\Admin\Exports\Reports\ExportReport;

class OrdersCountRep {

    protected $name;
    protected $data;

    public function __construct(array $data) {
        $this->name = "orders-count";
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

 
        $select = " SELECT name area_name, O.count as 'number_of_orders' 
                    from ( select area_id , count(id) as 'count' 
                           FROM (SELECT distinct orders.id AS id, area_id, orders.created_at,status
                           FROM orders
                           INNER JOIN order_items 
                           ON orders.id = order_items.order_id
                           WHERE order_items.product_id not in (1544 , 1632) ) AS orders 
                           where status = 'delivered' 
                           $dateRange
                           $area
                           group by area_id) O 
                    inner join area_translations 
                    on O.area_id = area_translations.area_id 
                    where locale = '$lang' ";

        $select = preg_replace("/\r|\n/", "", $select);

        $query = collect(DB::select(DB::raw($select)));
        $counter = 1;
        $mappedQuery = $query->map(
                function ($item) use (&$counter) {
                    $data['#'] = $counter++;
                    $data['area_name'] = $item->area_name;
                    $data['number_of_orders'] = $item->number_of_orders;
                    return $data;
                }, $query
        );
        return $mappedQuery;
    }

    public function getHeaddings() {
        return ['#',  'Name','Orders Count',];
    }

    public function getName() {
        return $this->name;
    }

    public function download() {
        return Excel::download(new ExportReport($this->getMappedQuery(), $this->getHeaddings()), $this->getName() . '.xlsx');
    }

}
