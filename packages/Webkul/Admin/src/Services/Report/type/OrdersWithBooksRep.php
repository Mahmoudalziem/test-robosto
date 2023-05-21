<?php

namespace Webkul\Admin\Services\Report\type;

use Webkul\Admin\Services\Report\ExportReprotService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Webkul\Admin\Exports\Reports\ExportReport;
use \Carbon\Carbon;

class OrdersWithBooksRep
{

    public $name;
    protected $data;

    public function __construct(array $data)
    {
        $this->name = "orders-with-books";
        $this->data = $data;
    }

    public function getMappedQuery()
    {

        $lang = $this->data['lang'];
        $dateFrom = $this->data['date_from'] ?? null;
        $dateTo = $this->data['date_to'] ?? null;
        $areaId = $this->data['area'] ?? null;

        if ($dateFrom && $dateTo) {
            $dateRange = " and orders.created_at >= '$dateFrom 00:00:00' and orders.created_at <= '$dateTo 23:59:59' ";
        } else {

            $dateFrom = Carbon::today()->subDays(30)->toDateString();
            $dateTo = Carbon::today()->toDateString();
            $dateRange = " and orders.created_at >= '$dateFrom 00:00:00' and orders.created_at <= '$dateTo 23:59:59' ";
        }


        if ($areaId) {
            $area = " and orders.area_id= $areaId ";
        } else {
            $area = "  ";
        }
        $delivery_time = config('robosto.DELIVERY_TIME');
        $select = " select
                            orders.increment_id,
                            A.name area,
                            W.name warehouse,
                            orders.status status,
                            sub_total,
                            delivery_chargs,
                            discount,
                            orders.discount_type,
                            final_total ,
                            orders.created_at createdAt,
                            DATE_ADD(orders.created_at, INTERVAL $delivery_time MINUTE) expectedAt,
                            customers.name customer,
                            customers.phone phone,
                            drivers.name  driver
                    from (select  orders.id from orders
                                        inner join order_items on orders.id=order_items.order_id
                                        where product_id in (1544 , 1632)
                                        group by orders.id) as ods
                    inner join orders on ods.id = orders.id
                    inner join customers on orders.customer_id = customers.id
                    inner join drivers on orders.driver_id = drivers.id
                    inner join area_translations as A on orders.area_id = A.area_id
                    inner join warehouse_translations as W on orders.warehouse_id = W.warehouse_id
                    where orders.status <> 'cancelled'
                    $dateRange
                    $area
                    and A.locale = '$lang'
                    and W.locale = '$lang'  ";

        $select = preg_replace("/\r|\n/", "", $select);

        $query = collect(DB::select(DB::raw($select)));
        $counter = 1;
        $mappedQuery = $query->map(
            function ($item) use (&$counter) {
                $data['#'] = $counter++;
                $data['increment_id'] = $item->increment_id;
                $data['area'] = $item->area;
                $data['warehouse'] = $item->warehouse;
                $data['status'] = $item->status;
                $data['sub_total'] = $item->sub_total == 0 ? '0' : $item->sub_total;
                $data['delivery_chargs'] = $item->delivery_chargs  == 0 ? '0' : $item->delivery_chargs;
                $data['discount'] = $item->discount  == 0 ? '0' : $item->discount;
                $data['discount_type'] = $item->discount_type ? '(' . (string) $item->discount_type . ')' : '-';
                $data['final_total'] = $item->final_total  == 0 ? '0' : $item->final_total;
                $data['createdAt'] = Carbon::parse($item->createdAt )->toDateString();
                $data['expectedAt'] = $item->expectedAt;
                $data['customer'] = $item->customer;
                $data['phone'] = $item->phone;
                $data['driver'] = $item->driver;

                return $data;
            },
            $query
        );
        return $mappedQuery;
    }

    public function getHeaddings()
    {
        return ['#', 'order id', 'area', 'warehouse', 'status', 'subTotal', 'deliveryChargs', 'discount', 'discountType', 'finalTotal', 'createdAt', 'expectedAt', 'customer', 'phone', 'driver'];
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