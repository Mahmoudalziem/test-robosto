<?php

namespace Webkul\Admin\Services\Report\type;

use Webkul\Admin\Services\Report\ExportReprotService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Webkul\Admin\Exports\Reports\ExportReport;

class BasketAverageRepVerTowRep implements WithHeadingRow {

    public $name;
    protected $data;
    private $headings = ['area'];
    private $list = [];

    public function __construct(array $data) {
        $this->name = "basket-average-ver-2";
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
            $areaCondition = " and area_id = {$areaId}";
        } else {
            $areaCondition = "  ";
        }

        $select = " SELECT
                    name, total_amount, orders_count, orders_avg_basket
                    FROM
                    (SELECT
                    area_id,
                    SUM(sub_total) AS 'total_amount',
                    COUNT(sub_total) AS 'orders_count',
                    AVG(sub_total) AS 'orders_avg_basket'
                    FROM
                    (SELECT distinct orders.id AS id, area_id,sub_total, orders.created_at,status
                           FROM orders
                           INNER JOIN order_items 
                           ON orders.id = order_items.order_id
                           WHERE order_items.product_id not in (1544 , 1632) ) AS orders
                    WHERE orders.status in( 'delivered','cancelled')
                    $dateRange
                    $areaCondition
                    GROUP BY area_id) O
                    INNER JOIN
                    area_translations ON area_translations.area_id = O.area_id
                    WHERE locale =  '" . $lang . "' ";

        $select = preg_replace("/\r|\n/", "", $select);

        $query = collect(DB::select(DB::raw($select)));
        $counter = 1;
        $this->list['total_amount'] = ['total_amount'];
        $this->list['orders_count'] = ['orders_count'];
        $this->list['orders_avg_basket'] = ['orders_avg_basket'];
        $mappedQuery = $query->map(
                function ($item) use (&$counter) {
                    $data['#'] = $counter++;
                    $data['name'] = $item->name;
                    $data['total_amount'] = $item->total_amount;
                    $data['orders_count'] = $item->orders_count;
                    $data['orders_avg_basket'] = $item->orders_avg_basket;
                    array_push($this->headings, $item->name);

                    array_push($this->list['total_amount'], $item->total_amount);
                    array_push($this->list['orders_count'], $item->orders_count);
                    array_push($this->list['orders_avg_basket'], $item->orders_avg_basket);
                    return $data;
                }, $query
        );

        return collect($this->list);
        return $mappedQuery;
    }

    public function getHeaddings() {
        return array_values($this->headings);
        return ['#', 'Area', 'Total amount', 'Orders count', 'Orders avg basket'];
    }

    public function getName() {
        return $this->name;
    }

    public function download() {
        return Excel::download(new ExportReport($this->getMappedQuery(), $this->getHeaddings()), $this->getName() . '.xlsx');
    }

}
