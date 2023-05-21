<?php

namespace Webkul\Admin\Services\Report\type;

use Webkul\Admin\Services\Report\ExportReprotService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Webkul\Admin\Exports\Reports\ExportReport;
use Webkul\Area\Models\Area;
use Webkul\Inventory\Models\InventoryWarehouse;
use Webkul\Inventory\Models\InventoryProduct;
use Webkul\Inventory\Models\Warehouse;
use Carbon\Carbon;

class InventoryWarehouseStockValue {

    public $name;
    protected $data;
    private $headings = ['#', 'Area', 'Warehouse', 'Amount Before Discount', 'ِAmount', 'Date'];
    private $areas;
    protected $remainingQty;

    public function __construct(array $data) {
        $this->name = "inventory-warehouse-stock-value";
        $this->data = $data;
        $areaId = $this->data['area'] ?? null;
        if ($areaId) {
            $this->areas = Area::where('id', $areaId)->get('id', 'name');
        } else {
            $this->areas = Area::get('id', 'name');
        }
    }

    public function getMappedQuery() {
        $lang = $this->data['lang'];
        $areaId = $this->data['area'] ?? null;
        $dateFrom = $this->data['date_from'] ?? null;
        $dateTo = $this->data['date_to'] ?? null;

        if ($areaId) {
            $area = " and inventory_stock_values.area_id = $areaId ";
        } else {
            $area = "  ";
        }


        if ($dateFrom) {
            $dateRange = " build_date = '$dateFrom' ";
        } else {
            $dateFrom = Carbon::now()->toDateString();
            $dateRange = " build_date = '$dateFrom' ";
        }


        $select = " SELECT AT.name a_name,WT.name w_name, amount_before_discount,amount,build_date 
                                        FROM  inventory_stock_values
                                        inner join area_translations as  AT on inventory_stock_values.area_id =AT.area_id
                                        inner join warehouse_translations as WT on inventory_stock_values.warehouse_id =WT.warehouse_id
                                        where $dateRange
                                        $area    
                                        and AT.locale = '$lang'
                                        and WT.locale = '$lang' ";

        $select = preg_replace("/\r|\n/", "", $select);

        $query = collect(DB::select(DB::raw($select)));

        $counter = 1;
        $mappedQuery = $query->map(
                function ($item) use (&$counter) {
                    $data['#'] = $counter++;
                    $data['a_name'] = $item->a_name;
                    $data['w_name'] = $item->w_name;
                    $data['amount_before_discount'] = $item->amount_before_discount;
                    $data['amount'] = $item->amount;
                    $data['date'] = $item->build_date;
                    return $data;
                }, $query
        );

        $total = ['#' => '', 'a_name' => 'اﻻجمــالي', 'w_name' => '---------------------', 'amount_before_discount' => $mappedQuery->sum('amount_before_discount'), 'amount' => $mappedQuery->sum('amount')];
        $mappedQuery->push($total);
        return $mappedQuery;
    }

    public function getHeaddings() {
        return $this->headings;
    }

    public function getName() {
        return $this->name;
    }

    public function download() {
        return Excel::download(new ExportReport($this->getMappedQuery(), $this->getHeaddings()), $this->getName() . '.xlsx');
    }

}
