<?php

namespace Webkul\Driver\Services;

use Carbon\Carbon;
use Webkul\Sales\Models\Order;
use Webkul\Driver\Models\Driver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Webkul\Sales\Models\OrderAddress;
use Webkul\Sales\Models\OrderComment;
use Webkul\Driver\Models\MonthlyBonus;
use Webkul\Driver\Models\WorkingCycle;
use Webkul\Driver\Models\WorkingCycleOrder;
use Illuminate\Database\Eloquent\Collection;
use Webkul\Core\Services\LocationService\Distance\DistanceService;
use Webkul\Driver\Models\DriverLogLogin;

class CalculateOrdersAndWorkingTime
{
    protected $driver;


    /**
     * @param Driver $driver
     * 
     * @return void
     */
    public function startCalculate(Driver $driver)
    {
        $this->driver = $driver;

        Log::info("Driver => " . $this->driver->id);

        // Get Active Monthly Bonus
        $activeMonthlyBonus = $this->getActiveMonthlyBonus();
        Log::info("1- Driver {$this->driver->id} Active Monthly Done");

        // Collect Data
        $workingTime = $this->getNumberOfWokringTime();
        Log::info("2- Driver {$this->driver->id} Number Of Working Time Done");
        $ordersCount = $this->getNumberOfOrders();
        Log::info("3- Driver {$this->driver->id} Number Of Orders Done");
        $customerRating = $this->getCustomerRating();
        Log::info("4- Driver {$this->driver->id} Customer Rating Done");
        
        // Save Collected Data
        $activeMonthlyBonus->no_of_orders += $ordersCount;
        $activeMonthlyBonus->no_of_working_hours += $workingTime;
        $activeMonthlyBonus->cutomer_ratings = $customerRating;
        $activeMonthlyBonus->save();
    }

    /**
     * @return MonthlyBonus
     */
    private function getActiveMonthlyBonus()
    {
        $activeMonthlyBonus = MonthlyBonus::where('driver_id', $this->driver->id)
            ->whereYear('created_at', now()->format('Y'))
            ->whereMonth('created_at', now()->format('m'))
            ->first();


        if (!$activeMonthlyBonus) {
            $activeMonthlyBonus = MonthlyBonus::create(['driver_id' => $this->driver->id]);
        }

        return $activeMonthlyBonus;
    }

    /**
     * @return mixed
     */
    private function getNumberOfWokringTime()
    {
        $driverLoginLogs = DriverLogLogin::
            where('driver_id', $this->driver->id)->
            whereBetween('created_at', ['2021-11-01 00:00:00', '2021-11-08 23:59:59'])->get();
        $tmpOnline = null;
        $mins = 0;
        foreach ($driverLoginLogs as $key => $log) {
            if ($log->action == 'online') {
                $tmpOnline = $log->created_at;
            }

            if ($key != 0 && $log->action == 'offline' && $tmpOnline != null) {
                $mins += Carbon::parse($log->created_at)->diffInHours(Carbon::parse($tmpOnline));
                $tmpOnline = null;
            }
        }

        return $mins;

        $q = "SELECT F.driver_id, ROUND((SUM(F.dif) / 3600)) AS Working_time FROM (SELECT A.id AS A_id, B.id AS B_id, A.driver_id, A.action, A.created_at, (B.created_at - A.created_at) AS dif
                    FROM driver_log_logins A CROSS JOIN driver_log_logins B
                    WHERE
                        B.action = 'offline'
                            AND B.id IN (SELECT 
                                MIN(C.id)
                            FROM
                                driver_log_logins C
                            WHERE
                                C.id > A.id)
                    ORDER BY A.id ASC
                    ) AS F
                    where F.driver_id = {$this->driver->id} and created_at between '2021-11-01 00:00:00' and '2021-11-08 23:59:59'
                    GROUP BY driver_id";

        $data = (array) DB::select(DB::raw($q));

        return isset($data[0]) ? $data[0]->Working_time : 0;
    }


    /**
     * @return mixed
     */
    private function getNumberOfOrders()
    {
        $q = "SELECT COUNT(O.id) AS orders_count, O.driver_id FROM orders AS O 
                WHERE driver_id = {$this->driver->id} and status = 'delivered' and created_at BETWEEN '2021-11-01 00:00:00' and '2021-11-08 23:59:59'";
        
        $data = (array) DB::select(DB::raw($q));

        return isset($data[0]) ? $data[0]->orders_count : 0;
    }


    private function getCustomerRating()
    {
        $orders = Order::where('status', Order::STATUS_DELIVERED)
            ->where('driver_id', $this->driver->id)
            ->get();

        $ordersIds = $orders->whereBetween('created_at', [Carbon::parse('2021-11-01 00:00:00'), Carbon::parse('2021-11-08 23:59:00')])->pluck('id')->toArray();
        
        if (!count($ordersIds)) {
            return 0;
        }

        $avgRating = $this->calculateAvgRating($ordersIds);
        $avgRatingInPercents = $this->convertAvgRatingToPercentage($avgRating);

        return ($avgRatingInPercents / 100) * 3;
    }

    /**
     * @param array $ordersIds
     * 
     * @return float
     */
    private function calculateAvgRating(array $ordersIds)
    {
        $ratings = OrderComment::whereIn('order_id', $ordersIds)->get();

        $oneRatings = $ratings->where('rating', 1)->count();
        $twoRatings = $ratings->where('rating', 2)->count();
        $threeRatings = $ratings->where('rating', 3)->count();
        $fourRatings = $ratings->where('rating', 4)->count();
        $fiveRatings = $ratings->where('rating', 5)->count();

        $sumTotal = $oneRatings + $twoRatings + $threeRatings + $fourRatings + $fiveRatings;

        if ($sumTotal < 1) {
            return 0;
        }

        $averageRating = (($oneRatings * 1) + ($twoRatings * 2) + ($threeRatings * 3) + ($fourRatings * 4) + ($fiveRatings * 5))
            / ($sumTotal);

        return round($averageRating, 1);
    }

    /**
     * @param float $averageRating
     * 
     * @return float
     */
    private function convertAvgRatingToPercentage(float $averageRating)
    {
        return ($averageRating / 5) * 100;
    }
}
