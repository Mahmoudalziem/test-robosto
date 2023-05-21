<?php

namespace Webkul\Admin\Services\Report\type;

use Webkul\Admin\Services\Report\ExportReprotService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Webkul\Admin\Exports\Reports\ExportReport;

class ProuductMostSoldRep {

    public $name;
    protected $data;

    public function __construct(array $data) {
        $this->name = "product-most-sold";
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

        if ($subcategoryId) {
            $subcategory = " and PT.product_id in (
					select product_id from product_sub_categories 
                    where sub_category_id in ($subcategoryId)
                    ) ";
        } else {
            $subcategory = "  ";
        }        

        $select = "select PT.product_id ,  PT.name product, T.qty_sold 
            from (SELECT product_id , sum(qty_shipped) as 'qty_sold' 
            FROM order_items inner join orders on 
            order_items.order_id = orders.id 
            where orders.status = 'delivered' 
            $dateRange
            $area
            group by product_id) T 
            INNER JOIN product_translations PT on 
            T.product_id = PT.product_id 
            inner join product_sub_categories 
            on PT.product_id = product_sub_categories.product_id 
            where PT.locale = '$lang' 
            $subcategory
            order by T.qty_sold desc";  
        
        $select = preg_replace("/\r|\n/", "", $select);
        $query = collect(DB::select(DB::raw($select)));
        $counter = 1;
        $mappedQuery = $query->map(
                function ($item) use (&$counter) {
                    $data['#'] = $counter++;
                    $data['product_id'] = $item->product_id;
                    $data['product'] = $item->product;
                    $data['qty'] = $item->qty_sold;

                    return $data;
                }, $query
        );
        return $mappedQuery;
    }

    public function getHeaddings() {
        return ['#', 'Proudct ID', 'Name', 'Qty Sold'];
    }

    public function getName() {
        return $this->name;
    }
    
    public function download() {
        return Excel::download(new ExportReport($this->getMappedQuery(), $this->getHeaddings()), $this->getName().'.xlsx');
    }     

}
