<?php

namespace Webkul\Admin\Services\Report\type;

use Webkul\Admin\Services\Report\ExportReprotService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Webkul\Admin\Exports\Reports\ExportReport;

class PurchaseAtleastFiveOrders
{

    public $name;
    protected $data;

    public function __construct(array $data)
    {
        $this->name = "purchase-atleast-five-orders";
        $this->data = $data;
    }
    
    public function getMappedQuery()
    {

        $lang = $this->data['lang'];
        $dateFrom = $this->data['date_from'] ?? null;
        $dateTo = $this->data['date_to'] ?? null;
        $areaId = $this->data['area'] ?? null;


        if ($dateFrom && $dateTo) {
            $dateRange = "  ODS.created_at >= '$dateFrom 00:00:00' and ODS.created_at <= '$dateTo 23:59:59' ";
        } else {
            $dateRange = "  ";
        }

        if ($areaId) {
            $area = " and orders.area_id= $areaId ";
        } else {
            $area = "  ";
        }



        $select = "SELECT   DISTINCT DATE_FORMAT(orders.created_at, '%m-%Y') month_year,
                            customer_id ,
                            name,
                            (select count(o.id) ods_count from orders o where o.customer_id = orders.customer_id and DATE_FORMAT(o.created_at, '%m-%Y') =DATE_FORMAT(orders.created_at, '%m-%Y'))  no_orders
                            FROM orders
                            inner join customers on orders.customer_id  = customers.id
                            where orders.created_at  > DATE_SUB(now(), INTERVAL 5 MONTH)
                            and orders.status = 'delivered'
                            having no_orders >= 5";

        $select = preg_replace("/\r|\n/", "", $select);
        $query = collect(DB::select(DB::raw($select)));
        $counter = 1;
        $mappedQuery = $query->map(
            function ($item) use (&$counter) {
                $data['#'] = $counter++;
                $data['month_year'] = $item->month_year;
                $data['customer_id'] = $item->customer_id;
                $data['name'] = $item->name;
                $data['no_orders'] = $item->no_orders;
                return $data;
            },
            $query
        );
        return $mappedQuery;
    }

    public function getHeaddings()
    {
        return ['#', 'ِِcustomer_id', 'ِِname', 'no_orders'];
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