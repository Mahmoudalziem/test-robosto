<?php

namespace Webkul\Admin\Services\Report\type;

use Webkul\Admin\Services\Report\ExportReprotService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Webkul\Admin\Exports\Reports\ExportReport;

class InventoryProductRep {

    public $name;
    protected $data;

    public function __construct(array $data) {
        $this->name = "inventory-product";
        $this->data = $data;
    }

    public function getMappedQuery() {

        $lang = $this->data['lang'];
        $areaId = $this->data['area'] ?? null;


        if ($areaId) {
            $area = " AND IW.area_id = $areaId ";
        } else {
            $area = "  ";
        }

        $select = "SELECT W.name warehouse, IW.qty , PT.name product, SubCatTrans.name sub_category, PT.product_id, barcode FROM
                inventory_warehouses IW 
                INNER JOIN product_translations PT on IW.product_id = PT.product_id
                INNER JOIN products  on IW.product_id = products.id
                INNER JOIN warehouse_translations W on IW.warehouse_id= W.warehouse_id
                
                INNER JOIN product_sub_categories AS PSC  on IW.product_id = PSC.product_id
                INNER JOIN sub_category_translations AS SubCatTrans on SubCatTrans.sub_category_id = PSC.sub_category_id

                where PT.locale = '{$lang}'" . " AND W.locale = '{$lang}'" . " AND SubCatTrans.locale = '{$lang}'" . $area;

        $select = preg_replace("/\r|\n/", "", $select);
        
        $query = collect(DB::select(DB::raw($select)));
        
        $counter = 1;
        $mappedQuery = $query->map(
                function ($item) use (&$counter) {
                    $data['#'] = $counter++;
                    $data['warehouse'] = $item->warehouse;
                    $data['product_id'] = $item->product_id;  
                    $data['barcode'] = '(' . (string) $item->barcode .')' ;                    
                    $data['product'] = $item->product;
                    $data['sub_category'] = $item->sub_category;
                    $data['qty'] =  $item->qty==0 ? '0' : $item->qty;                    
                    return $data;
                }, $query
        );
        return $mappedQuery;
    }

    public function getHeaddings() {
        return ['#', 'Warehouse', 'Product id', 'Barcode', 'Product', 'Sub Category', 'Qty'];
    }

    public function getName() {
        return $this->name;
    }
    
    public function download() {
        return Excel::download(new ExportReport($this->getMappedQuery(), $this->getHeaddings()), $this->getName() . '.xlsx');
    }    

}
