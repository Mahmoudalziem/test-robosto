<?php

namespace Webkul\Admin\Services\Report\type;

use Webkul\Admin\Services\Report\ExportReprotService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Webkul\Admin\Exports\Reports\ExportReport;

class ProductsWithPricesAndLastPOpriceRep {

    public $name;
    protected $data;

    public function __construct(array $data) {
        $this->name = "products-with-prices-and-last-po-price";
        $this->data = $data;
    }

    public function getMappedQuery() {

        $lang = $this->data['lang'];

        $select = "SELECT 
                            products.id,
                            PT.name,
                            barcode,
                            products.price,
                            (	SELECT cost
                                FROM purchase_order_products
                                INNER JOIN purchase_orders 
                                ON purchase_orders.id = purchase_order_products.purchase_order_id
                                WHERE product_id = PT.product_id
                                AND is_draft = 0
                                ORDER BY purchase_orders.created_at DESC
                                LIMIT 1) AS 'PO_cost'
                        FROM    product_translations PT
                                INNER JOIN
                            products ON PT.product_id = products.id
                        WHERE PT.locale = '" . $lang . "'  
                        AND products.status = 1 ";

        $select = preg_replace("/\r|\n/", "", $select);
        
        $query = collect(DB::select(DB::raw($select)));
        $counter = 1;
        $mappedQuery = $query->map(
                function ($item) use (&$counter) {
                    $data['#'] = $counter++;
                    $data['product_id'] = $item->id;
                    $data['barcode'] = '(' . (string) $item->barcode .')' ;                     
                    $data['name'] = $item->name;
                    $data['PO_cost'] = $item->PO_cost !=0 ?$item->PO_cost : '0';
                    $data['price'] = $item->price;
                    
                    return $data;
                }, $query
        );
        return $mappedQuery;
    }

    public function getHeaddings() {
        return ['#', 'Product ID', 'Barcode', 'Name', 'PO cost', 'price'];
    }

    public function getName() {
        return $this->name;
    }
    
    public function download() {
        return Excel::download(new ExportReport($this->getMappedQuery(), $this->getHeaddings()), $this->getName() . '.xlsx');
    }    

}
