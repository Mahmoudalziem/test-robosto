<?php

namespace Webkul\Admin\Services\Report\type;

use Webkul\Admin\Services\Report\ExportReprotService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Webkul\Admin\Exports\Reports\ExportReport;
use \Carbon\Carbon;

class TimeToZeroRep {

    public $name;
    protected $data;

    public function __construct(array $data) {
        $this->name = "time-to-zero-report";
        $this->data = $data;
    }

    public function getMappedQuery() {
        $areaId = $this->data['area'] ?? null;
        if ($areaId) {
            $area = " and inventory_warehouses.area_id= $areaId ";
        } else {
            $area = "  ";
        }
        $select = "SELECT
        (SELECT
        barcode
        FROM
        products
        WHERE
        id = inventory_warehouses.product_id) AS 'barcode',
        (SELECT
        name
        FROM
        product_translations
        WHERE
        product_id = inventory_warehouses.product_id
        AND locale = 'ar') AS 'product',
        (SELECT
        name
        FROM
        warehouse_translations
        WHERE
        warehouse_id = inventory_warehouses.warehouse_id
        AND locale = 'ar') AS 'warehouse',
        qty AS 'quantity_in_stock',
        sold_last_x_days,
        sold_last_x_days / 10 AS 'average_per_day',
        qty * 10 / sold_last_x_days AS 'days_to_zero'
        FROM
        inventory_warehouses
        INNER JOIN
        (SELECT
        warehouse_id,
        product_id,
        SUM(qty_shipped) AS 'sold_last_x_days'
        FROM
        orders
        INNER JOIN order_items
        WHERE
        orders.id = order_items.order_id
        AND status = 'delivered'
        AND orders.created_at BETWEEN DATE_SUB(NOW(), INTERVAL 10 DAY) AND NOW()
        GROUP BY warehouse_id , product_id) T ON inventory_warehouses.warehouse_id = T.warehouse_id
        AND inventory_warehouses.product_id = T.product_id
        AND qty > 0      
         $area
                  ;";

        $select = preg_replace("/\r|\n/", "", $select);

        $query = collect(DB::select(DB::raw($select)));
        $counter = 1; 
        $mappedQuery = $query->map(
                function ($item) use (&$counter) {

                    $data['#'] = $counter++;
                    $data['barcode'] = $item->barcode;
                    $data['product'] = $item->product;
                    $data['warehouse'] = $item->warehouse;
                    $data['quantity_in_stock'] = $item->quantity_in_stock;
                    $data['average_per_day'] = $item->average_per_day;
                    $data['days_to_zero'] = $item->days_to_zero;
                    $data['sold_last_x_days'] = $item->sold_last_x_days;
                    return $data;
                }, $query
        );
        return $mappedQuery;
    }

    public function getHeaddings() {
        return ['#', 'barcode', 'product', 'warehouse', 'quantity_in_stock', 'average_per_day', 'days_to_zero', 'sold_last_x_days'];
    }

    public function getName() {
        return $this->name;
    }

    public function download() {
        return Excel::download(new ExportReport($this->getMappedQuery(), $this->getHeaddings()), $this->getName() . '.xlsx');
    }

}
