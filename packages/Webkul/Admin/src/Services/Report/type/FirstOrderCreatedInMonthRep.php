<?php

namespace Webkul\Admin\Services\Report\type;

use Webkul\Admin\Services\Report\ExportReprotService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Webkul\Admin\Exports\Reports\ExportReport;

class FirstOrderCreatedInMonthRep {

    public $name;
    protected $data;

    public function __construct(array $data) {
        $this->name = "first-order-created-in-month";
        $this->data = $data;
    }

    public function getMappedQuery() {

        $lang = $this->data['lang'];
        $dateFrom = $this->data['date_from'] ?? null;
        $dateTo = $this->data['date_to'] ?? null;
        $areaId = $this->data['area'] ?? null;        
        $subcategoryId = $this->data['subcategory'] ?? null;
        
        if ($dateFrom && $dateTo) {
            $dateRange = " and orders.created_at >= '$dateFrom 00:00:00' and orders.created_at <= '$dateTo 23:59:59' ";            
        } else {
            $dateRange = "  ";
        }
        
        if($areaId){
            $area= " and orders.area_id= $areaId ";
        }else{
            $area= "  ";
        }          
       

        $select = " SELECT * FROM orders
                    WHERE customer_id IN (SELECT customer_id FROM orders
                            WHERE status = 'delivered'
                            GROUP BY customer_id
                            HAVING COUNT(customer_id) = 1)
                    $dateRange 
                    $area ";  
        
        $select = preg_replace("/\r|\n/", "", $select);
        $query = collect(DB::select(DB::raw($select)));
        $counter = 1;
        $mappedQuery = $query->map(
                function ($item) use (&$counter) {
                    $data['#'] = $counter++;
                    $data['id'] = $item->id;
                    $data['increment_id'] = $item->increment_id;
                    $data['status'] = $item->status;
                    $data['final_total'] = $item->final_total;                    
                    $data['customer_id'] = $item->customer_id; 
                    $data['area_id'] = $item->area_id;                    
                    $data['warehouse_id'] = $item->warehouse_id; 
                    $data['channel_id'] = $item->channel_id;                                        
                    $data['created_at'] = $item->created_at;

                    return $data;
                }, $query
        );
        return $mappedQuery;
    }

    public function getHeaddings() {
        return ['#', 'ID', 'increment_id', 'status', 'final_total','customer_id','area_id','warehouse_id','channel_id','created_at'];
    }

    public function getName() {
        return $this->name;
    }
    
    public function download() {
        return Excel::download(new ExportReport($this->getMappedQuery(), $this->getHeaddings()), $this->getName().'.xlsx');
    }     

}
