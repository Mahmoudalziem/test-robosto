<?php

namespace Webkul\Admin\Services\Report\type;

use Webkul\Admin\Services\Report\ExportReprotService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Webkul\Admin\Exports\Reports\ExportReport;

class StockAndSoldSpesifiedProductsRep {

    protected $name;
    protected $data;

    public function __construct(array $data ) {
        $this->name = "stock-and-sold-specified-products";
        $this->data = $data;
    }

    public function getMappedQuery() {

        $dateFrom = $this->data['date_from'] ?? null;
        $dateTo = $this->data['date_to'] ?? null;
        $areaId = $this->data['area'] ?? null;

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



        $select = " SELECT T.qty_sold , I.total_qty as 'qty_in_stock' ,PT.name,PT.barcode,PT.product_id , T.price_sold_without_discount , T.price_sold_with_discount , area_translations.name as 'area_name' from
                    (
                    SELECT product_id , sum(qty_shipped) as 'qty_sold' , sum(base_total) as 'price_sold_without_discount' , sum(total) as 'price_sold_with_discount'
                    FROM order_items inner join orders 
                    on order_items.order_id = orders.id
                    where orders.status = 'delivered'
                    and product_id in (4859,4865,4871,4879,4886,4892,4899,4905,4911,4919,4925,4931,4937,4943,4949,4955,4961,4967,4973,4979,4985,4991,4997,5003,5009,5015,5021,5027,5039,5045,5051,5057,5063,5069,5074,4858,4864,4869,4880,4885,4891,4897,4904,4910,4918,4924,4930,4936,4942,4948,4954,4960,4966,4972,4978,4984,4990,4996,5002,5008,5014,5020,5026,5032,5033,5044,5050,5056,5062,5068,5073,4857,4863,4868,4878,4884,4890,4896,4903,4909,4917,4923,4929,4935,4941,4947,4953,4959,4965,4971,4977,4983,4989,4995,5001,5007,5013,5019,5025,5031,5037,5043,5049,5055,5061,5067,5072,4856,4862,4867,4877,4883,4889,4898,4902,4908,4916,4922,4928,4934,4940,4946,4952,4958,4964,4970,4976,4982,4988,4994,5000,5006,5012,5018,5024,5030,5036,5042,5048,5054,5060,5066,5071,4855,4861,4866,4875,4882,4888,4895,4901,4907,4915,4920,4927,4933,4939,4945,4951,4957,4963,4969,4975,4981,4987,4993,4999,5005,5011,5017,5023,5029,5035,5041,5047,5053,5058,5065,5070,4860,4870,4876,4881,4887,4894,4900,4906,4914,4921,4926,4932,4938,4944,4950,4956,4962,4968,4974,4980,4986,4992,4998,5004,5010,5016,5022,5028,5034,5040,5046,5052,5059,5064,5075,1798,1800) 
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
                    INNER JOIN (select product_id , sum(total_qty) as 'total_qty' ,area_id
                                            from inventory_areas 
                                            group by product_id , area_id ) I 
                                on I.product_id = T.product_id
                    INNER JOIN area_translations on I.area_id = area_translations.area_id 
                    where area_translations.locale = 'ar'
                    and PT.locale = 'ar'
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
                    $data['price_sold_with_discount'] = $item->price_sold_with_discount;
                    $data['price_sold_without_discount'] = $item->price_sold_without_discount;
                    $data['area_name'] = $item->area_name;
                    return $data;
                }, $query
        );
        return $mappedQuery;
    }

    public function getHeaddings() {
        return ['#', 'Product Id','barcode', 'Name', 'Qty in stock' , 'Sold Qty' ,'Total Price With Discount','Total Price WithOut Discount' ,'area_name'];
    }

    public function getName() {
        return $this->name;
    }
    
    public function download() {
        return Excel::download(new ExportReport($this->getMappedQuery(), $this->getHeaddings()), $this->getName().'.xlsx');
    }    

}
