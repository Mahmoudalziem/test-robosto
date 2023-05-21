<?php

namespace Webkul\Admin\Services\Report\type;

use Webkul\Admin\Services\Report\ExportReprotService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Webkul\Admin\Exports\Reports\ExportReport;

class InvProdSkuBarcodeQtyRep {

    public $name;
    protected $data;

    public function __construct(array $data) {
        $this->name = "inventory-product-barcode-sku-qty";
        $this->data = $data;
    }

    public function getMappedQuery() {

        $lang = $this->data['lang'];
        $dateFrom = $this->data['date_from'] ?? null;
        $dateTo = $this->data['date_to'] ?? null;
        $areaId = $this->data['area'] ?? null;

        if ($dateFrom && $dateTo) {
            $dateRange = " and created_at >= '$dateFrom 00:00:00' and created_at <= '$dateTo 23:59:59' ";            
        } else {
            $dateRange = "  ";
        }

        if ($areaId) {
            $area = " and area_id= $areaId ";
        } else {
            $area = "  ";
        }

        $select = " select  AREA.name area_name,WRH.name warehouse_name,PRD.name p_name,PRD.barcode,INV_PRD.sku ,INV_PRD.qty ,INV_PRD.exp_date, INV_PRD.created_at
                    from (	select area_id,warehouse_id ,product_id,sku,sum(qty) qty,exp_date ,created_at
                            from inventory_products
                            where id is not null
                            $dateRange
                            $area
                            group by area_id,warehouse_id,product_id,sku,exp_date,created_at
                                    order by area_id,warehouse_id,product_id 
                            ) INV_PRD
                    inner join (select A.id,name  from areas A
                                            inner join area_translations AT
                                            on A.id = AT.area_id
                                            where AT.locale= '$lang' ) AREA
                    on INV_PRD.area_id = AREA.id  
                    inner join (select W.id, name  from warehouses W
                                            inner join warehouse_translations WT
                                            on W.id = WT.warehouse_id
                                            where WT.locale= '$lang'
                                ) WRH
                    on INV_PRD.warehouse_id = WRH.id 
                    inner join (
                                            select P.id,name  ,barcode ,created_at    from products P
                                            inner join product_translations PT
                                            on P.id = PT.product_id
                                            where PT.locale= '$lang' 
                                ) PRD
                    on INV_PRD.product_id = PRD.id;";

        $select = preg_replace("/\r|\n/", "", $select);
        
        $query = collect(DB::select(DB::raw($select)));
        $counter = 1;
        $mappedQuery = $query->map(
                function ($item) use (&$counter) {
                    $data['#'] = $counter++;
                    $data['area_name'] = $item->area_name;
                    $data['warehouse_name'] = $item->warehouse_name;                    
                    $data['p_name'] = $item->p_name;
                    $data['barcode'] = $item->barcode;                    
                    $data['sku'] = $item->sku;    
                    $data['qty'] = $item->qty; 
                    $data['exp_date'] = $item->exp_date;                     
                    $data['created_at'] = $item->created_at;   
                    return $data;
                }, $query
        );
        return $mappedQuery;
    }

    public function getHeaddings() {
        return ['#', 'area_name', 'warehouse_name', 'product_name', 'barcode', 'sku', 'qty', 'exp_date', 'created_at'];
    }

    public function getName() {
        return $this->name;
    }

    public function download() {
        return Excel::download(new ExportReport($this->getMappedQuery(), $this->getHeaddings()), $this->getName() . '.xlsx');
    }

}
