<?php

namespace Webkul\Admin\Services\Report\type;

use Webkul\Admin\Services\Report\ExportReprotService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Webkul\Admin\Exports\Reports\ExportReport;

class CountUpdatedOrdersInAreaRep {

    public $name;
    protected $data;

    public function __construct(array $data) {
        $this->name = "count-updated-orders-in-area";
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

        $select = " select 
                           up_ords.area_id,
                            name area,
                            up_ords.total_orders, 
                            up_ords.scheduled_orders,
                            up_ords.cancelled_orders,
                            up_ords.delivered_orders
                            from  (select 
                                    ods.area_id,
                                    coalesce(count( ods.id   )                       , 0) total_orders ,  
                                    coalesce(count(if(ods.status = 'scheduled' ,1,null) ), 0) scheduled_orders ,
                                    coalesce(count(if(ods.status = 'cancelled' ,1,null) ), 0) cancelled_orders ,
                                    coalesce(count(if(ods.status = 'delivered' ,1,null) ), 0) delivered_orders 
                        from (SELECT distinct orders.id AS id, area_id, orders.created_at,status
                                   FROM orders
                                   INNER JOIN order_items ON orders.id = order_items.order_id
                                   WHERE order_items.product_id not in (select distinct  product_id  from product_sub_categories where sub_category_id in (39, 40, 50))
                                   AND order_items.product_id NOT IN ( 1544 , 1632 )
                                   AND orders.is_updated = '1' 
                                   $dateRange
                                   group by orders.id) AS ods
 
                    group by ods.area_id) up_ords
                    inner join area_translations 
                    on up_ords.area_id = area_translations.area_id 
                    where  locale= '$lang' ";

        $select = preg_replace("/\r|\n/", "", $select);

        $query = collect(DB::select(DB::raw($select)));
        $counter = 1;
        $mappedQuery = $query->map(
                function ($item) use (&$counter) {
                    $data['#'] = $counter++;
                    $data['area'] = $item->area;
                    $data['total_orders'] =   $item->total_orders == 0 ? '0' : $item->total_orders;
                    $data['scheduled_orders'] = $item->scheduled_orders == 0 ? '0' : $item->scheduled_orders;
                    $data['cancelled_orders'] = $item->cancelled_orders == 0 ? '0' : $item->cancelled_orders;
                    $data['delivered_orders'] = $item->delivered_orders == 0 ? '0' : $item->delivered_orders;                 
                    return $data;
                }, $query
        );
        return $mappedQuery;

    }

    public function getHeaddings() {
        return ['#', 'area' , 'total orders' , 'scheduled orders' , 'cancelled orders' , 'delivered orders' ];
    }

    public function getName() {
        return $this->name;
    }

    public function download() {
        return Excel::download(new ExportReport($this->getMappedQuery(), $this->getHeaddings()), $this->getName() . '.xlsx');
    }

}
