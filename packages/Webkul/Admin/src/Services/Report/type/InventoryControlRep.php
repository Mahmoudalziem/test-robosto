<?php

namespace Webkul\Admin\Services\Report\type;

use Webkul\Admin\Services\Report\ExportReprotService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Webkul\Admin\Exports\Reports\ExportReport;
use Carbon\Carbon;
class InventoryControlRep {

    public $name;
    protected $data;

    public function __construct(array $data) {
        $this->name = "inventory-control";
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
            $area = " and area_id = $areaId ";
        } else {
            $area = "  ";
        }



        $select = "select * from (SELECT PS.inventory_control_id,IC.id,IC.warehouse,PS.product_id,inventory_qty,PS.shipped_qty,PS.qty,PS.qty_stock,start_date,end_date,valid,status  
                    FROM product_stocks PS
                    INNER JOIN (
                    SELECT inv_con.id,inv_con.area_id ,name warehouse ,inv_con.start_date ,inv_con.end_date
                    from ( SELECT id,area_id,warehouse_id  ,start_date,end_date  
                            FROM inventory_controls
                            where is_completed = 1
                            and is_active = 0
                            $area
                            order by id desc LIMIT 1  )  inv_con
                        INNER JOIN warehouse_translations
                        on warehouse_translations.warehouse_id=inv_con.warehouse_id
                        where warehouse_translations.locale = '$lang'
                        ) IC
                    ON IC.id = PS.inventory_control_id
                    where PS.is_default = 1 ) QRY
                    INNER JOIN  (select product_id,barcode,name,price,locale from products
                                inner join product_translations
                                on products.id = product_translations.product_id)
                                as product_translations
                    on product_translations.product_id =  QRY.product_id
                    where product_translations.locale = '$lang' ";

        $select = preg_replace("/\r|\n/", "", $select);
 
        $query = collect(DB::select(DB::raw($select)));
       // dd($query->first());
        $counter = 1;
        $mappedQuery = $query->map(
                function ($item) use (&$counter) {
                    $data['#'] = $counter++;
                    $data['id'] = $item->inventory_control_id;
                    $data['warehouse'] = $item->warehouse;
                    $data['barcode'] = '(' . (string) $item->barcode .')' ;       
                    $data['price'] = '(' . (string) $item->price .')' ;       
                    $data['product'] = $item->name;
                    $data['status'] = $item->status? 'scanned': 'not scanned';
                    $data['valid'] = $item->valid ? 'valid': 'not valid' ;                    
                    $data['inventory_qty'] =  $item->inventory_qty==0 ? '0' :  $item->inventory_qty;   
                    $data['qty_stock'] =  $item->qty_stock==0 ? '0' :  $item->qty_stock; 
                    $data['diff'] =  $item->inventory_qty - $item->qty_stock; 
                    $data['shipped_qty'] = $item->shipped_qty ==0 ? '0' :  $item->shipped_qty; 
                    $data['start_date'] = Carbon::parse($item->start_date)->format('Y-m-d');
                    $data['end_date'] = Carbon::parse($item->end_date)->format('Y-m-d'); 
//                    $data['qty'] = $item->qty_sold;                    
//                    $data['product_id'] = $item->product_id;
//                    $data['product'] = $item->product;
//                    $data['qty'] = $item->qty_sold;

                    return $data;
                }, $query
        );
        return $mappedQuery;
    }

    public function getHeaddings() {
        return ['#', 'ID', 'warehouse', 'Barcode','Price', 'Product Name', 'status', 'valid', 'System Count', 'Store Count','Diff', 'Live Orders' , 'start_date', 'end_date' ,'Actual','Final Result' , 'Total Lost','Adjust','Total Stock','Total Value'];
    }

    public function getName() {
        return $this->name;
    }

    public function download() {
        return Excel::download(new ExportReport($this->getMappedQuery(), $this->getHeaddings()), $this->getName() . '.xlsx');
    }

}
