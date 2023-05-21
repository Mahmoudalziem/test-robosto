<?php

namespace Webkul\Admin\Services\Report\type;

use Webkul\Admin\Services\Report\ExportReprotService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Webkul\Admin\Exports\Reports\ExportReport;

class AdjustmentCostRep {

    public $name;
    protected $data;

    public function __construct(array $data) {
        $this->name = "adjustment-cost";
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

        $select = " SELECT 
                        SUM(inventory_adjustment_products.qty * purchase_order_products.cost_before_discount) AS 'multply'
                    FROM inventory_adjustment_products
                    INNER JOIN purchase_order_products 
                    ON inventory_adjustment_products.sku = purchase_order_products.sku
                    WHERE   inventory_adjustment_products.status <> 3
                            AND inventory_adjustment_id IN (SELECT id FROM inventory_adjustments
                            WHERE status = 2 
                            $dateRange ) ";

        $select = preg_replace("/\r|\n/", "", $select);

        $query = collect(DB::select(DB::raw($select)));
        $counter = 1;
        $mappedQuery = $query->map(
                function ($item) use (&$counter) {
                    $data['#'] = $counter++;
                    $data['multply'] = $item->multply;

                    return $data;
                }, $query
        );
        return $mappedQuery;
    }

    public function getHeaddings() {
        return ['#', 'multply' ];
    }

    public function getName() {
        return $this->name;
    }

    public function download() {
        return Excel::download(new ExportReport($this->getMappedQuery(), $this->getHeaddings()), $this->getName() . '.xlsx');
    }

}
