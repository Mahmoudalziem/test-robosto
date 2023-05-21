<?php

namespace Webkul\Admin\Services\Report\type;

use Webkul\Admin\Services\Report\ExportReprotService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Webkul\Admin\Exports\Reports\ExportReport;
use \Carbon\Carbon;

class AdjustmentReportRep {

    public $name;
    protected $data;

    public function __construct(array $data) {
        $this->name = "adjustment-report";
        $this->data = $data;
    }

    public function getMappedQuery() {

        $dateFrom = $this->data['date_from'] ?? null;
        $dateTo = $this->data['date_to'] ?? null;
        $areaId = $this->data['area'] ?? null;

        if ($dateFrom && $dateTo) {
            $dateRange = " and inventory_adjustments.created_at >= '$dateFrom 00:00:00' and inventory_adjustments.created_at <= '$dateTo 23:59:59' ";
        } else {

            $dateFrom = Carbon::today()->subDays(30)->toDateString();
            $dateTo = Carbon::today()->toDateString();
            $dateRange = " and inventory_adjustments.created_at >= '$dateFrom 00:00:00' and inventory_adjustments.created_at <= '$dateTo 23:59:59' ";
        }


        if ($areaId) {
            $area = " and inventory_adjustments.area_id= $areaId ";
        } else {
            $area = "  ";
        }
        $select = "SELECT 
            inventory_adjustments.id,
            inventory_adjustments.created_at,
            products.barcode,
            products.price,
            inventory_adjustment_products.status,
            product_translations.name as 'product_name',
            area_translations.name as 'area_name',
            inventory_adjustment_products.sku,
            inventory_adjustment_products.qty,
            inventory_adjustment_products.note,
            admins.name as 'approved_by'
        FROM
            backend_prod.inventory_adjustments
                INNER JOIN
            inventory_adjustment_products ON inventory_adjustments.id = inventory_adjustment_products.inventory_adjustment_id
                INNER JOIN
            products ON products.id = inventory_adjustment_products.product_id
                INNER JOIN
            product_translations ON product_translations.product_id = products.id
                INNER JOIN
            inventory_adjustment_actions ON inventory_adjustments.id = inventory_adjustment_actions.inventory_adjustment_id
                INNER JOIN
            area_translations ON area_translations.area_id = inventory_adjustments.area_id
            inner join admins on admins.id = inventory_adjustment_actions.admin_id
        WHERE
            inventory_adjustments.status = 2
                AND product_translations.locale = 'ar'
                AND area_translations.locale = 'ar'
                AND action = 'approved'       
                                $dateRange 
                                $area
                  ;";

        $select = preg_replace("/\r|\n/", "", $select);

        $query = collect(DB::select(DB::raw($select)));
        $counter = 1; 
        $mappedStatuses = ["1"=>"LOST","2"=>"EXPIRED","3"=>"OVERQTY","4"=>"DAMAGED","5"=>"RETURN_TO_VENDOR"];
        $mappedQuery = $query->map(
                function ($item) use (&$counter,$mappedStatuses) {

                    $data['#'] = $counter++;
                    $data['adjustment_id'] = $item->id;
                    $data['area'] = $item->area_name;
                    $data['status'] = $mappedStatuses[$item->status];
                    $data['product_name'] = $item->product_name;
                    $data['barcode'] = '( ' . $item->barcode . ' )';
                    $data['sku'] = '( ' . $item->sku . ' )';
                    $data['qty'] = $item->qty;
                    $data['price'] = $item->price;
                    $data['amount'] = $item->qty * $item->price;
                    $data['note'] = '( ' . (string) $item->note . ' )';
                    $data['approved_by'] = $item->approved_by;
                    $data['created_at'] = Carbon::parse($item->created_at)->toDateString();
                    return $data;
                }, $query
        );
        return $mappedQuery;
    }

    public function getHeaddings() {
        return ['#', 'adjustment_id', 'area', 'status', 'product_name', 'barcode', 'sku', 'qty','price','amount', 'note', 'approved_by', 'created_at'];
    }

    public function getName() {
        return $this->name;
    }

    public function download() {
        return Excel::download(new ExportReport($this->getMappedQuery(), $this->getHeaddings()), $this->getName() . '.xlsx');
    }

}
