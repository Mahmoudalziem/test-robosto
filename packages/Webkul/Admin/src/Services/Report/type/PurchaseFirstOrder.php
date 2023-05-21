<?php

namespace Webkul\Admin\Services\Report\type;

use Webkul\Admin\Services\Report\ExportReprotService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Webkul\Admin\Exports\Reports\ExportReport;

class PurchaseFirstOrder {

    public $name;
    protected $data;

    public function __construct(array $data) {
        $this->name = "purchase-first-order";
        $this->data = $data;
    }

    public function getMappedQuery() {

        $lang = $this->data['lang'];
        $dateFrom = $this->data['date_from'] ?? null;
        $dateTo = $this->data['date_to'] ?? null;
        $areaId = $this->data['area'] ?? null;        

        
        if ($dateFrom && $dateTo) {
            $dateRange = "  ODS.created_at >= '$dateFrom 00:00:00' and ODS.created_at <= '$dateTo 23:59:59' ";            
        } else {
            $dateRange = "  ";
        }
        
        if($areaId){
            $area= " and orders.area_id= $areaId ";
        }else{
            $area= "  ";
        }          

        $notShippment = " and shippment_id is null ";


        $select = "SELECT  ODS.area_id,AT.name ,count(ODS.customer_id) customer_count
                                        FROM orders AS ODS
                                        INNER JOIN (
                                           SELECT customer_id, MIN(id) AS firstOrder
                                           FROM orders
                                           where status='delivered'
                                           $notShippment
                                           GROUP BY customer_id
                                        ) AS ORD 
                                        ON ORD.customer_id = ORD.customer_id AND ORD.firstOrder= ODS.id
                                        INNER JOIN area_translations as AT on AT.area_id= ODS.area_id
                                        where $dateRange
                                        and AT.locale='$lang'
                                        group by area_id
                                        order by area_id";  
        
        $select = preg_replace("/\r|\n/", "", $select);
        $query = collect(DB::select(DB::raw($select)));
        $counter = 1;
        $mappedQuery = $query->map(
                function ($item) use (&$counter) {
                    $data['#'] = $counter++;
                    $data['name'] = $item->name;
                    $data['customer_count'] = $item->customer_count;
                    return $data;
                }, $query
        );
        return $mappedQuery;
    }

    public function getHeaddings() {
        return ['#', 'ِِArea', '1st Purchases'];
    }

    public function getName() {
        return $this->name;
    }
    
    public function download() {
        return Excel::download(new ExportReport($this->getMappedQuery(), $this->getHeaddings()), $this->getName().'.xlsx');
    }     

}
