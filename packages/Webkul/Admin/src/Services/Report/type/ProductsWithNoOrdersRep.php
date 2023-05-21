<?php

namespace Webkul\Admin\Services\Report\type;

use Webkul\Admin\Services\Report\ExportReprotService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Webkul\Admin\Exports\Reports\ExportReport;

class ProductsWithNoOrdersRep {

    public $name;
    protected $data;

    public function __construct(array $data) {
        $this->name = "prodcuts-with-no-orders";
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
            $area = " and orders.area_id= $areaId ";
        } else {
            $area = "  ";
        }

        $select = " SELECT 
                        name, description, qty AS 'total_in_stock', barcode, weight
                    FROM
                        (SELECT P.product_id, qty, name, description
                        FROM (SELECT * FROM product_translations
                        WHERE product_id NOT IN (SELECT product_id FROM order_items
                                WHERE id > 0
                                $dateRange
                                GROUP BY product_id) ) P
                        INNER JOIN (SELECT product_id, SUM(total_qty) AS 'qty' 
                        FROM inventory_areas
                        GROUP BY product_id) O 
                        ON O.product_id = P.product_id
                        WHERE O.qty > 0 
                        AND locale = '" . $lang . "' ) IO
                        INNER JOIN products ON products.id = IO.product_id ";

        $select = preg_replace("/\r|\n/", "", $select);

        $query = collect(DB::select(DB::raw($select)));
        $counter = 1;
        $mappedQuery = $query->map(
                function ($item) use (&$counter) {
                    $data['#'] = $counter++;
                    $data['name'] = $item->name;
                    $data['total_in_stock'] = $item->total_in_stock;
                    $data['barcode'] = $item->barcode;
                    $data['weight'] = $item->weight;
                    $data['description'] = $item->description;

                    return $data;
                }, $query
        );
        return $mappedQuery;
    }

    public function getHeaddings() {
        return ['#', 'name', 'total_in_stock', 'barcode', 'weight', 'description'];
    }

    public function getName() {
        return $this->name;
    }

    public function download() {
        return Excel::download(new ExportReport($this->getMappedQuery(), $this->getHeaddings()), $this->getName() . '.xlsx');
    }

}
