<?php

namespace Webkul\Admin\Services\Report\type;

use Webkul\Admin\Services\Report\ExportReprotService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Webkul\Admin\Exports\Reports\ExportReport;

class StockAndSoldRep {

    protected $name;
    protected $data;

    public function __construct(array $data ) {
        $this->name = "stock-and-sold";
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

        $select = " SELECT T.qty_sold , I.total_qty as 'qty_in_stock' ,PT.name,PT.barcode,PT.product_id from
                    (
                    SELECT product_id , sum(qty_shipped) as 'qty_sold'
                    FROM order_items inner join orders 
                    on order_items.order_id = orders.id
                    where orders.status = 'delivered' 
                    $dateRange
                    $area     
                    group by product_id
                    ) T
                    INNER JOIN 
                    (select product_id,barcode , name , locale 
                    from products 
                    inner join product_translations 
                    ON products.id = product_translations.product_id 
                    ) PT on T.product_id = PT.product_id
                    INNER JOIN (select product_id , sum(total_qty) as 'total_qty' 
                                            from inventory_areas 
                                            group by product_id ) I 
                                on I.product_id = T.product_id
                    where PT.locale = '$lang'
                    $subcategory
                    order by T.qty_sold desc ";

        $select = preg_replace("/\r|\n/", "", $select);
 
        $query = collect(DB::select(DB::raw($select)));
        $counter = 1;
        $mappedQuery = $query->map(
                function ($item) use (&$counter) {
                    $data['#'] = $counter++;
                    $data['product_id'] = $item->product_id;
                    $data['barcode'] = '( '.$item->barcode. ' )'  ;                    
                    $data['name'] = $item->name;
                    $data['qty_in_stock'] = $item->qty_in_stock;
                    $data['qty_sold'] = $item->qty_sold;
                    return $data;
                }, $query
        );
        return $mappedQuery;
    }

    public function getHeaddings() {
        return ['#', 'Product Id','barcode', 'Name', 'Qty in stock' , 'Sold Qty'];
    }

    public function getName() {
        return $this->name;
    }
    
    public function download() {
        return Excel::download(new ExportReport($this->getMappedQuery(), $this->getHeaddings()), $this->getName().'.xlsx');
    }    

}
