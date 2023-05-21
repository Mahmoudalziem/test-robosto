<?php

namespace Webkul\Admin\Services\Report\type;

use Webkul\Admin\Services\Report\ExportReprotService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Webkul\Admin\Exports\Reports\ExportReport;

class RobostoSuppliersRep
{

    public $name;
    protected $data;

    public function __construct(array $data)
    {
        $this->name = "robosto-supplier";
        $this->data = $data;
    }

    public function getMappedQuery()
    {

        $dateFrom = $this->data['date_from'] ?? null;
        $dateTo = $this->data['date_to'] ?? null;


        if ($dateFrom && $dateTo) {
            $dateRange = " and created_at >= '$dateFrom 00:00:00' and created_at <= '$dateTo 23:59:59' ";
        } else {
            $dateRange = "  ";
        }

        $select = "SELECT purchase_order_no,total_cost,(SELECT name FROM warehouse_translations WHERE  warehouse_id = purchase_orders.warehouse_id  AND locale = 'ar') as 'warehouse',
            (SELECT 
                name
            FROM
                suppliers
            WHERE
                id = purchase_orders.supplier_id) as 'supplier' ,
                created_at
    FROM
        purchase_orders
    WHERE
        supplier_id IN (72 , 66 , 83) and is_draft = 0 $dateRange ;";

        $select = preg_replace("/\r|\n/", "", $select);

        $query = collect(DB::select(DB::raw($select)));

        $counter = 1;
        $mappedQuery = $query->map(
            function ($item) use (&$counter) {
                $data['#'] = $counter++;
                $data['purchase_order_no'] = $item->purchase_order_no;
                $data['total_cost'] = $item->total_cost;
                $data['warehouse'] = $item->warehouse;
                $data['supplier'] = $item->supplier;
                $data['created_at'] = $item->created_at;
                return $data;
            },
            $query
        );
        return $mappedQuery;
    }

    public function getHeaddings()
    {
        return ['#', 'purchase_order_no', 'total_cost', 'warehouse', 'supplier', 'created_at'];
    }

    public function getName()
    {
        return $this->name;
    }

    public function download()
    {
        return Excel::download(new ExportReport($this->getMappedQuery(), $this->getHeaddings()), $this->getName() . '.xlsx');
    }
}
