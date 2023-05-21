<?php

namespace Webkul\Admin\Services\Report\type;

use Webkul\Admin\Services\Report\ExportReprotService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Webkul\Admin\Exports\Reports\ExportReport;
use \Carbon\Carbon;

class OrdersWithNoBooksRep {

    public $name;
    protected $data;

    public function __construct(array $data) {
        $this->name = "orders-with-no-books";
        $this->data = $data;
    }

    public function getMappedQuery() {

        $lang = $this->data['lang'];
        $dateFrom = $this->data['date_from'] ?? null;
        $dateTo = $this->data['date_to'] ?? null;
        $areaId = $this->data['area'] ?? null;
        $notShippment = " and orders.shippment_id is null ";

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
        $select = " select   orders.id,orders.increment_id,A.name area,W.name warehouse,orders.status status,orders.created_at createdAt, orders.updated_at updatedAt,
                            orders.scheduled_at scheduledAt,  customers.name customer,customers.phone phone,drivers.name  driver,order_cancel_reasons.reason reason, admins.name cancelled_by
                    from (select  orders.id from orders
                                    inner join order_items on orders.id=order_items.order_id
                                    where product_id not in (1544 , 1632)
                                    group by orders.id) as ods
                    inner join orders on ods.id = orders.id
                    inner join customers on orders.customer_id = customers.id
                    inner join drivers on orders.driver_id = drivers.id
                    left join order_cancel_reasons on orders.id=order_cancel_reasons.order_id
                    left join activity_log on orders.id = activity_log.subject_id and action_type = 'order-cancelled'
                    left join admins on admins.id = activity_log.causer_id
                    inner join area_translations as A on orders.area_id = A.area_id
                    inner join warehouse_translations as W on orders.warehouse_id = W.warehouse_id
                    where orders.id > 1
                    $notShippment
                    $dateRange
                    $area
                    and A.locale = '$lang'
                    and W.locale = '$lang'  ";

        $select = preg_replace("/\r|\n/", "", $select);

        $query = collect(DB::select(DB::raw($select)));
        $counter = 1;
        $orderCancelled = $this->getOrdersCanceledForCustomerInSameDay();
        $mappedQuery = $query->map(
                function ($item) use (&$counter, $delivery_time, $orderCancelled) {
                    $orderDate = $item->status == 'scheduled' ? $item->scheduledAt : $item->createdAt;
                    $data['#'] = $counter++;
                    $data['increment_id'] = $item->increment_id;
                    $data['area'] = $item->area;
                    $data['warehouse'] = $item->warehouse;
                    $data['status'] = $item->status;
                    $data['orderDate'] = Carbon::parse($item->createdAt)->toDateString();
                    $data['createdAt'] = $item->createdAt;
                    $data['updatedAt'] = $item->updatedAt;
                    $data['expectedAt'] = Carbon::parse($orderDate)->addMinutes($delivery_time)->format('Y-m-d H:i:s');
                    $data['customer'] = $item->customer;
                    $data['phone'] = '( ' . $item->phone . ' )';
                    $data['driver'] = $item->driver;
                    $data['cancel_reason'] = '( ' . (string) $item->reason . ' )';
                    // if there cancelled order in same day with delivered order
                    if (in_array($item->id, $orderCancelled)) {
                        if (!$item->reason) {
                            $data['cancel_reason'] = '( Duplicated )';
                        }
                    }
                    $data['cancelled_by'] = $item->cancelled_by;
                    return $data;
                }, $query
        );
        return $mappedQuery;
    }

    public function getOrdersCanceledForCustomerInSameDay() {
        $select = "   select distinct Ord_cancelled.id
                                        from orders as Ord_cancelled
                                        inner join orders as Ord_delivered
                                        on Ord_delivered.customer_id =Ord_cancelled.customer_id
                                        and DATE(Ord_delivered.created_at) =DATE(Ord_cancelled.created_at)
                                        where Ord_cancelled.status='cancelled'
                                        and Ord_delivered.status='delivered';";

        $select = preg_replace("/\r|\n/", "", $select);
        $query = DB::select(DB::raw($select));
        return array_column($query, 'id');
    }

    public function getHeaddings() {
        return ['#', 'order id', 'area', 'warehouse', 'status', 'orderDate', 'createdAt', 'updatedAt', 'expectedAt', 'customer', 'phone', 'driver', 'cancel_reason', 'cancelled_by'];
    }

    public function getName() {
        return $this->name;
    }

    public function download() {
        return Excel::download(new ExportReport($this->getMappedQuery(), $this->getHeaddings()), $this->getName() . '.xlsx');
    }

}
