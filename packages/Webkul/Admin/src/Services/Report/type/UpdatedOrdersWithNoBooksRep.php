<?php

namespace Webkul\Admin\Services\Report\type;

use Webkul\Admin\Services\Report\ExportReprotService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Webkul\Admin\Exports\Reports\ExportReport;
use \Carbon\Carbon;

class UpdatedOrdersWithNoBooksRep {

    public $name;
    protected $data;

    public function __construct(array $data) {
        $this->name = "updated-orders-with-no-books";
        $this->data = $data;
    }

    public function getMappedQuery() {

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
        $select = " SELECT 
                                            orders.id,
                                            orders.increment_id,
                                            A.name area,
                                            W.name warehouse,
                                            orders.status status,
                                            orders.paid_type paid_type,
                                            orders.created_at createdAt,
                                            orders.updated_at updatedAt,
                                            orders.scheduled_at scheduledAt,
                                            customers.name customer,
                                            customers.phone phone,
                                            drivers.name driver,
                                            order_cancel_reasons.reason reason,
                                            admins.name cancelled_by
                                        FROM (SELECT orders.id FROM orders
                                                        INNER JOIN order_items ON orders.id = order_items.order_id
                                                        WHERE product_id NOT IN (1544 , 1632)
                                                        AND is_updated = '1'
                                                        GROUP BY orders.id) AS ods
                                                INNER JOIN orders ON ods.id = orders.id
                                                INNER JOIN customers ON orders.customer_id = customers.id
                                                INNER JOIN drivers ON orders.driver_id = drivers.id
                                                LEFT JOIN order_cancel_reasons ON orders.id = order_cancel_reasons.order_id
                                                LEFT JOIN activity_log ON orders.id = activity_log.subject_id
                                                AND action_type = 'order-cancelled'
                                                LEFT JOIN admins ON admins.id = activity_log.causer_id
                                                INNER JOIN area_translations AS A ON orders.area_id = A.area_id
                                                INNER JOIN warehouse_translations AS W ON orders.warehouse_id = W.warehouse_id
                    WHERE   orders.id > 1
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
                    $data['paid_type'] = $item->paid_type;
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
        return ['#', 'order id', 'area', 'warehouse', 'status', 'paid_type', 'orderDate', 'createdAt', 'updatedAt', 'expectedAt', 'customer', 'phone', 'driver', 'cancel_reason', 'cancelled_by'];
    }

    public function getName() {
        return $this->name;
    }

    public function download() {
        return Excel::download(new ExportReport($this->getMappedQuery(), $this->getHeaddings()), $this->getName() . '.xlsx');
    }

}
