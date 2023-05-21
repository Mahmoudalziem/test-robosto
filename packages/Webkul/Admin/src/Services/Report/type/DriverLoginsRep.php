<?php

namespace Webkul\Admin\Services\Report\type;

use Webkul\Admin\Services\Report\ExportReprotService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Webkul\Admin\Exports\Reports\ExportReport;

class DriverLoginsRep {

    protected $name;
    protected $data;

    public function __construct(array $data) {
        $this->name = "driver-logins";
        $this->data = $data;
    }

    public function getMappedQuery() {

        $lang = $this->data['lang'];
        $dateFrom = $this->data['date_from'] ?? null;
        $dateTo = $this->data['date_to'] ?? null;
        $areaId = $this->data['area'] ?? null;
        
        if ($dateFrom && $dateTo) {
            $dateRange = " and driver_log_logins.created_at >= '$dateFrom 00:00:00' and driver_log_logins.created_at <= '$dateTo 23:59:59' ";
        } else {
            $dateRange = "  ";
        }

        if ($areaId) {
            $area = " and drivers.area_id= $areaId ";
        } else {
            $area = "  ";
        }

 
        $select = " SELECT name driver_name, action , driver_log_logins.created_at as 'action_time' 
                    FROM driver_log_logins 
                    inner join drivers 
                    on driver_log_logins.driver_id = drivers.id 
                    where drivers.id > 0 
                    $dateRange 
                    $area ";

        $select = preg_replace("/\r|\n/", "", $select);
 
        $query = collect(DB::select(DB::raw($select)));
        $counter = 1;
        $mappedQuery = $query->map(
                function ($item) use (&$counter) {
                    $data['#'] = $counter++;
                    $data['driver_name'] = $item->driver_name;
                    $data['action_time'] = $item->action_time;
                    return $data;
                }, $query
        );
        return $mappedQuery;
    }

    public function getHeaddings() {
        return ['#',  'Driver','Action Time',];
    }

    public function getName() {
        return $this->name;
    }

    public function download() {
        return Excel::download(new ExportReport($this->getMappedQuery(), $this->getHeaddings()), $this->getName() . '.xlsx');
    }

}
