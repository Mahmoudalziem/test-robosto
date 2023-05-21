<?php

namespace Webkul\Admin\Services\Report\type;

use Webkul\Admin\Services\Report\ExportReprotService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Webkul\Admin\Exports\Reports\ExportReport;
use \Carbon\Carbon;

class OperationsRep {

    public $name;
    protected $data;

    public function __construct(array $data) {
        $this->name = "operations-report";
        $this->data = $data;
    }

    public function getMappedQuery() {

        $dateFrom = $this->data['date_from'] ?? null;
        $dateTo = $this->data['date_to'] ?? null;
        $areaId = $this->data['area'] ?? null;

        if ($dateFrom && $dateTo) {
            $dateRange = " and o.created_at >= '$dateFrom 00:00:00' and o.created_at <= '$dateTo 23:59:59' ";
        } else {
            $dateFrom = Carbon::today()->subDays(30)->toDateString();
            $dateTo = Carbon::today()->toDateString();
            $dateRange = " and o.created_at >= '$dateFrom 00:00:00' and o.created_at <= '$dateTo 23:59:59' ";
        }


        if ($areaId) {
            $area = " and o.area_id= $areaId ";
        } else {
            $area = "  ";
        }
        $select = "SELECT 
            o.id AS order_id,
            o.increment_id as increment_order_id,
            o.status AS order_status,
            wt.name AS warehouse_name,
            DATE(o.created_at) AS order_date,
            TIME(o.created_at) AS order_time,
            o.created_at + INTERVAL 1 HOUR AS 'expeted',
            c.name AS customer_name,
            c.phone AS customer_phone_number,
            d.name AS driver_name,
            o.final_total,
            o.paid_type,
            oc.rating,
            oc.comment,
            (EXISTS (SELECT 1
                FROM order_logs_actual ol
                WHERE ol.log_time > o.created_at + INTERVAL 1 HOUR
                      AND ol.log_type = 'order_driver_items_delivered'
                      AND ol.order_id = o.id)) AS is_order_late_more_than_1_hour,
            (EXISTS (SELECT 1
                FROM order_logs_actual ol
                WHERE ol.log_time > o.created_at + INTERVAL 2 HOUR
                      AND ol.log_type = 'order_driver_items_delivered'
                      AND ol.order_id = o.id)) AS is_order_late_more_than_2_hours,
            o.is_updated,
            (EXISTS (SELECT 1
                FROM orders
                WHERE customer_id = c.id and id <> o.id and status = 'delivered' )) AS has_other_delivered_orders,
            admins.name as 'cancelled_by' , 
            order_cancel_reasons.reason as 'reason'
        FROM
            orders o
                LEFT JOIN
            customers c ON o.customer_id = c.id
                LEFT JOIN
            warehouses w ON o.warehouse_id = w.id
                JOIN
            warehouse_translations wt ON wt.warehouse_id = w.id
                LEFT JOIN
            drivers d ON o.driver_id = d.id
                LEFT JOIN
            order_comments oc ON o.id = oc.order_id
                LEFT JOIN
            activity_log ON o.id = activity_log.subject_id
                AND action_type = 'order-cancelled'
                LEFT JOIN
            admins ON admins.id = activity_log.causer_id
            left join order_cancel_reasons on o.id=order_cancel_reasons.order_id
        WHERE
            locale = 'ar'
            and o.shippment_id is null 
            and o.id not in (select order_id from order_items where product_id in (1544 , 1632))
            {$dateRange}
            {$area}
        ";

        $select = preg_replace("/\r|\n/", "", $select);
        $query = collect(DB::select(DB::raw($select)));
        $counter = 1;
        $mappedQuery = $query->map(
                function ($item) use (&$counter) {
                    $orderDate = $item->order_status == 'scheduled' ? $item->scheduledAt : $item->expeted;
                    $data['#'] = $counter++;
                    $data['order_id'] = $item->order_id;
                    $data['increment_id'] = $item->increment_order_id;
                    $data['warehouse'] = $item->warehouse_name;
                    $data['status'] = $item->order_status;
                    $data['order_date'] = $item->order_date;
                    $data['order_time'] = $item->order_time;
                    $data['expected_at'] = $orderDate;
                    $data['customer'] = $item->customer_name;
                    $data['phone'] = '( ' . $item->customer_phone_number . ' )';
                    $data['driver'] = $item->driver_name;
                    $data['order_price'] = $item->final_total;
                    $data['payment_type'] = $item->paid_type;
                    $data['customer_rating'] = $item->rating;
                    $data['customer_comment'] = $item->comment;
                    $data['more than 1 H'] = $item->is_order_late_more_than_1_hour?"YES":"NO";
                    $data['more than 2 H'] = $item->is_order_late_more_than_2_hours?"YES":"NO";
                    $data['order_is_updated'] = $item->is_updated==0?'NO':'YES';
                    $data['first_order'] = $item->has_other_delivered_orders?"NO":"YES";
                    $data['cancel_reason'] = '( ' . (string) $item->reason . ' )';
                    $data['cancelled_by'] = $item->cancelled_by;
                    return $data;
                }, $query
        );
        return $mappedQuery;
    }

    public function getHeaddings() {
        return ['#', 'order_id','increment_id','warehouse','status','order_date','order_time','expected_at','customer','phone','driver','order_price','payment_type','customer_rating','customer_comment','more than 1 H','more than 2 H','order_is_updated','first_order','cancel_reason','cancelled_by'];
    }

    public function getName() {
        return $this->name;
    }

    public function download() {
        return Excel::download(new ExportReport($this->getMappedQuery(), $this->getHeaddings()), $this->getName() . '.xlsx');
    }

}
