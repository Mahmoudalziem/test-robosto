<?php

namespace Webkul\Admin\Services\Report\type;

use Webkul\Admin\Services\Report\ExportReprotService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Webkul\Admin\Exports\Reports\ExportReport;
use \Carbon\Carbon;

class DriverIncentiveRep {

    public $name;
    protected $data;

    public function __construct(array $data) {
        $this->name = "drivers-incentive";
        $this->data = $data;
    }

    public function getMappedQuery() {

        $lang = $this->data['lang'];
        $areaId = $this->data['area'] ?? null;   
        $dateFrom = $this->data['date_from'] ?? null;
        $dateTo = $this->data['date_to'] ?? null;

        if ($areaId) {
            $area = " AND drivers.area_id =  $areaId ";
        } else {
            $area = "  ";
        }
        
        if ($dateFrom && $dateTo) {
            $dateRange = " monthly_bonus.created_at >= '$dateFrom 00:00:00' and monthly_bonus.created_at <= '$dateTo 23:59:59' ";
        } else {

            $dateFrom = Carbon::today()->subDays(30)->toDateString();
            $dateTo = Carbon::today()->toDateString();
            $dateRange = " monthly_bonus.created_at >= '$dateFrom 00:00:00' and monthly_bonus.created_at <= '$dateTo 23:59:59' ";
        }

        $select = "   SELECT 
                                                        drivers.name driver_name,
                                                        AT.name area_name,
                                                        incentive,
                                                        no_of_orders,
                                                        no_of_working_hours,
                                                        cutomer_ratings,
                                                        supervisor_ratings,
                                                        no_of_orders_back_bonus,
                                                        working_path_ratings,
                                                        orders_bonus,
                                                        working_hours_bonus,
                                                        back_bonus,
                                                        equation
                                                    FROM monthly_bonus
                                                    INNER JOIN drivers ON drivers.id = monthly_bonus.driver_id
                                                    INNER JOIN area_translations AT ON drivers.area_id = AT.area_id
                                                    WHERE $dateRange
                                                   $area
                                                    AND locale= '$lang' 
                        ";

        $select = preg_replace("/\r|\n/", "", $select);
    
        $query = collect(DB::select(DB::raw($select)));

        $counter = 1;
        $mappedQuery = $query->map(
                function ($item) use (&$counter) {
                    $data['#'] = $counter++;
                    $data['driver_name'] = $item->driver_name;
                    $data['area_name'] = $item->area_name;
                    $data['incentive'] = $item->incentive;
                    $data['no_of_orders'] = $item->no_of_orders? '( ' . (string) $item->no_of_orders . ' )' : '0';
                    $data['no_of_working_hours'] = $item->no_of_working_hours? '( ' . (string) $item->no_of_working_hours . ' )' : '0';                
                    $data['cutomer_ratings'] = $item->cutomer_ratings? '( ' . (string) $item->cutomer_ratings . ' )' : '0';                
                    $data['supervisor_ratings'] = $item->supervisor_ratings? '( ' . (string) $item->supervisor_ratings . ' )' : '0';                                    
                    $data['no_of_orders_back_bonus'] = $item->no_of_orders_back_bonus? '( ' . (string) $item->no_of_orders_back_bonus . ' )' : '0';                                    
                    $data['working_path_ratings'] = $item->working_path_ratings? '( ' . (string) $item->working_path_ratings . ' )' : '0';                                                        
                    $data['orders_bonus'] = $item->orders_bonus? '( ' . (string) $item->orders_bonus . ' )' : '0';                                                                            
                    $data['working_hours_bonus'] = $item->working_hours_bonus? '( ' . (string) $item->working_hours_bonus . ' )' : '0';                     
                    $data['back_bonus'] = $item->back_bonus? '( ' . (string) $item->back_bonus . ' )' : '0';                                         
                    $data['equation'] = $item->equation? '( ' . (string) $item->equation . ' )' : '0';                                                             


                    return $data;
                },
                $query
        );
        return $mappedQuery;
    }

    public function getHeaddings() {
        return ['#', 'Driver', 'Area', 'Incentive', 'No. orders', 'No. Working hours', 'Cutomer ratings','Supervisor ratings', 'Orders back bonus', 'Working path ratings', 'Orders bonus','Working hours bonus','Back bonus','equation'];
    }

    public function getName() {
        return $this->name;
    }

    public function download() {
        return Excel::download(new ExportReport($this->getMappedQuery(), $this->getHeaddings()), $this->getName() . '.xlsx');
    }

}
