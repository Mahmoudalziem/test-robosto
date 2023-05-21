<?php

namespace Webkul\Admin\Services\Report\type;

use Webkul\Admin\Services\Report\ExportReprotService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Webkul\Admin\Exports\Reports\ExportReport;
use \Carbon\Carbon;

class OrdersRatingRep
{

    public $name;
    protected $data;

    public function __construct(array $data)
    {
        $this->name = "orders-rating";
        $this->data = $data;
    }

    public function getMappedQuery()
    {

        $lang = $this->data['lang'];
        $dateFrom = $this->data['date_from'] ?? null;
        $dateTo = $this->data['date_to'] ?? null;
        $areaId = $this->data['area'] ?? null;

        if ($dateFrom && $dateTo) {
            $dateRange = " AND orders.created_at >= '$dateFrom 00:00:00' and orders.created_at <= '$dateTo 23:59:59' ";
        } else {

            $dateFrom = Carbon::today()->subDays(30)->toDateString();
            $dateTo = Carbon::today()->toDateString();
            $dateRange = " AND orders.created_at >= '$dateFrom 00:00:00' and orders.created_at <= '$dateTo 23:59:59' ";
        }


        if ($areaId) {
            $area = " AND orders.area_id= $areaId ";
        } else {
            $area = "  ";
        }

        $select = " SELECT 
                                            increment_id,
                                            AreaTrans.name area_name,
                                            CUST.name customer_name,
                                            phone,
                                            rating,
                                            comment,
                                            orders.created_at createdAt
                                        FROM orders 
                                            INNER JOIN (select distinct order_id,comment,rating from order_comments ) OC ON orders.id = OC.order_id
                                            INNER JOIN customers CUST ON orders.customer_id = CUST.id
                                            INNER JOIN area_translations AreaTrans on orders.area_id = AreaTrans.area_id
                                        WHERE orders.status='delivered'
                                        $dateRange
                                        $area
                                        AND AreaTrans.locale = '$lang'    
                                        ORDER BY orders.created_at  
                        ";
       
        $select = preg_replace("/\r|\n/", "", $select);

        $query = collect(DB::select(DB::raw($select)));

        $counter = 1;
        $mappedQuery = $query->map(
            function ($item) use (&$counter) {
                $data['#'] = $counter++;
                $data['increment_id'] = $item->increment_id;
                $data['area_name'] = $item->area_name;
                $data['customer_name'] = $item->customer_name;
                $data['phone'] = $item->phone;
                $data['rating'] = $item->rating == 0 ? '0' : $item->rating;
                $data['comment'] = $item->comment ? '(' . (string) $item->comment . ')' : '-';
                $data['orderDate'] = Carbon::parse($item->createdAt)->toDateString();

                return $data;
            },
            $query
        );
        return $mappedQuery;
    }

    public function getHeaddings()
    {
        return ['#', 'Order ID', 'Area', 'Customer', 'phone', 'rating', 'comment','OrderDate'];
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