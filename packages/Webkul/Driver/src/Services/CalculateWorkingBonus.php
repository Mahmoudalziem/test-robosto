<?php
namespace Webkul\Driver\Services;

use Carbon\Carbon;
use Webkul\Sales\Models\Order;
use Webkul\Driver\Models\Driver;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Webkul\Sales\Models\OrderAddress;
use Webkul\Driver\Models\MonthlyBonus;
use Webkul\Driver\Models\WorkingCycle;
use Webkul\Driver\Models\WorkingCycleOrder;
use Illuminate\Database\Eloquent\Collection;
use Webkul\Core\Models\BonusVariable;
use Webkul\Core\Services\LocationService\Distance\DistanceService;
use Webkul\Driver\Models\DailyBonus;
use Webkul\Driver\Models\DriverLogLogin;
use Webkul\Sales\Models\OrderComment;

class CalculateWorkingBonus 
{
    protected $driver;
    protected $activeMonthlyBonus;
    protected $activeDailyBonus;

    public const DAY = 'day';
    public const MONTH = 'month';

    /**
     * @param Order $order
     * @param Driver $driver
     * 
     * @return void
     */
    public function __construct(Driver $driver)
    {
        $this->driver = $driver;

        $this->getActiveBonus();
    }

    /**
     * 1- Calculate Number Of Orders
     * 
     * @return void
     */
    public function calculateNumberOfOrders()
    {
        // Update Monthly Total Orders
        $this->activeMonthlyBonus->no_of_orders += 1;
        $this->activeMonthlyBonus->save();

        // Update Daily Total Orders
        $this->activeDailyBonus->no_of_orders += 1;
        $this->activeDailyBonus->save();

        // Check that the driver deserving bonus
        $ordersBonus = $this->checkBonusExist($this->activeMonthlyBonus->no_of_orders);
        if ($ordersBonus) {
            $this->activeMonthlyBonus->orders_bonus = $ordersBonus;
            $this->activeMonthlyBonus->save();
        }
    }
    
    
    /**
     * 2- Calculate Number Of Working Hours
     * 
     * @return void
     */
    public function calculateNumberOfWorkingHours()
    {
        $lastLogin = DriverLogLogin::where('driver_id', $this->driver->id)->where('action', Driver::AVAILABILITY_ONLINE)->latest()->first();
        $diffInHours = now()->diffInHours($lastLogin->created_at);
        
        $this->activeMonthlyBonus->no_of_working_hours += $diffInHours;
        $this->activeMonthlyBonus->save();

        $this->activeDailyBonus->no_of_working_hours += $diffInHours;
        $this->activeDailyBonus->save();

        $monthlyWorkingBonus = $this->checkBonusExist($this->activeMonthlyBonus->no_of_working_hours, 'working_hours', 'working_hours_bonus');
        if ($monthlyWorkingBonus) {
            $this->activeMonthlyBonus->working_hours_bonus = $monthlyWorkingBonus;
            $this->activeMonthlyBonus->save();
        }
    }
    
    /**
     * 3- Calculate Customer Ratings
     * 
     * @return void
     */
    public function calculateCustomersRating()
    {
        $orders = Order::where('status', Order::STATUS_DELIVERED)
                        ->where('driver_id', $this->driver->id)
                        ->get();

        $monthlyCustomersRating = $this->monthlyCustomersRating($orders);
        $this->activeMonthlyBonus->cutomer_ratings = $monthlyCustomersRating;
        $this->activeMonthlyBonus->save();


        $dailyCustomersRating = $this->dailyCustomersRating($orders);
        $this->activeDailyBonus->cutomer_ratings = $dailyCustomersRating;
        $this->activeDailyBonus->save();
        
    }

    /**
     * 4- Calculate Supervisor Ratings
     * 
     * @return void
     */
    public function calculateSupervisorRating()
    {
        $this->activeMonthlyBonus->supervisor_ratings = $this->driver->supervisor_rate;
        $this->activeMonthlyBonus->save();
    }
    
    
    /**
     * 5- Calculate Back Bonus
     * 
     * @return void
     */
    public function calculateBackBonus()
    {
        $this->activeMonthlyBonus->back_bonus += config('robosto.DRIVER_BACK_BONUS');
        $this->activeMonthlyBonus->no_of_orders_back_bonus += 1;
        $this->activeMonthlyBonus->save();
        
        
        $this->activeDailyBonus->back_bonus += config('robosto.DRIVER_BACK_BONUS');
        $this->activeDailyBonus->no_of_orders_back_bonus += 1;
        $this->activeDailyBonus->save();

    }

    /**
     * 6- Calculate Working Path Rating
     * 
     * @return void
     */
    public function calculateWorkingPathRating()
    {
        $workingCycles = WorkingCycle::where('status', WorkingCycle::DONE_STATUS)
            ->where('driver_id', $this->driver->id)
            ->whereYear('created_at', now()->format('Y'))
            ->whereMonth('created_at', now()->format('m'))
            ->get();

        $this->monthlyWorkingPathRating($workingCycles);
            
        $this->dailyWorkingPathRating($workingCycles);
    }

    /**
     * @return void
     */
    public function runTheEquation()
    {
        $evaluation = $this->activeMonthlyBonus->cutomer_ratings + $this->activeMonthlyBonus->supervisor_ratings + $this->activeMonthlyBonus->working_path_ratings;
        $totalBonus = $this->activeMonthlyBonus->orders_bonus + $this->activeMonthlyBonus->working_hours_bonus;

        $result = (($evaluation / 10) * $totalBonus) + $this->activeMonthlyBonus->back_bonus;
        $equation = "((Evaluation / 10) * (orders_bonus + working_hours_bonus) + back_bonus) => (( $evaluation / 10 ) * $totalBonus) + {$this->activeMonthlyBonus->back_bonus}";

        Log::alert([
            "Evaluation"    =>  $evaluation,
            "Orders+Hours"    =>  $totalBonus,
            "BackBonus"    =>  $this->activeMonthlyBonus->back_bonus,
            "Result"        =>  $result,
            "Equation" => $equation
        ]);

        $this->activeMonthlyBonus->bonus = $result;
        $this->activeMonthlyBonus->equation = $equation;
        $this->activeMonthlyBonus->save();

        $this->driver->incentive = $result;
        $this->driver->save();
    }
    
    /**
     * @param Collection $orders
     * 
     * @return void
     */
    private function monthlyCustomersRating(Collection $orders)
    {
        $ordersIds = $orders->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->pluck('id')->toArray();
        
        $avgRating = $this->calculateAvgRating($ordersIds);
        $avgRatingInPercents = $this->convertAvgRatingToPercentage($avgRating);

        return ($avgRatingInPercents / 100 ) * 3;
    }

    /**
     * @param Collection $orders
     * 
     * @return void
     */
    private function dailyCustomersRating(Collection $orders)
    {
        $ordersIds = $orders->whereBetween('created_at', [now()->startOfDay(), now()->endOfDay()])->pluck('id')->toArray();
        
        $avgRating = $this->calculateAvgRating($ordersIds);
        $avgRatingInPercents = $this->convertAvgRatingToPercentage($avgRating);

        return ($avgRatingInPercents / 100 ) * 3;
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
        if ($sumTotal == 0) {
            return 0;
        }

        $averageRating = (($oneRatings * 1) + ($twoRatings * 2) + ($threeRatings * 3) + ($fourRatings * 4) + ($fiveRatings * 5)) / ($sumTotal);

        return round($averageRating, 1);
    }

    /**
     * @param float $averageRating
     * 
     * @return float
     */
    private function convertAvgRatingToPercentage(float $averageRating)
    {
        return ($averageRating / 5 ) * 100;
    }

    /**
     * @return void
     */
    private function getActiveBonus()
    {
        $this->activeMonthlyBonus = $this->getActiveMonthlyBonus();

        $this->activeDailyBonus = $this->getActiveDailyBonus();
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
     * @return DailyBonus
     */
    private function getActiveDailyBonus()
    {
        $activeDailyBonus = DailyBonus::where('driver_id', $this->driver->id)
                    ->whereDate('created_at', now()->format('Y-m-d'))
                    ->first();
        
        if (!$activeDailyBonus) {
            $activeDailyBonus = DailyBonus::create(['driver_id' => $this->driver->id]);
        }

        return $activeDailyBonus;
    }

    /**
     * @param Collection $workingCycles
     * 
     * @return bool|void
     */
    private function monthlyWorkingPathRating(Collection $workingCycles)
    {
        $actualTime = $workingCycles->sum('actual_time');
        if ($actualTime == 0) {
            $actualTime = 1;
        }

        $workingPathRating = ($workingCycles->sum('expected_time') / $actualTime) * 6;
        $this->activeMonthlyBonus->working_path_ratings = $workingPathRating;
        $this->activeMonthlyBonus->save();
    }

    /**
     * @param Collection $workingCycles
     * 
     * @return bool|void
     */
    private function dailyWorkingPathRating(Collection $workingCycles)
    {
        $activeDailyBonus = $workingCycles->whereBetween('created_at', [now()->startOfDay(), now()->endOfDay()]);
        $actualTime = $activeDailyBonus->sum('actual_time');
        if ($actualTime == 0) {
            $actualTime = 1;
        }

        $workingPathRating = ($activeDailyBonus->sum('expected_time') / $actualTime) * 6;
        
        $this->activeDailyBonus->working_path_ratings = $workingPathRating;
        $this->activeDailyBonus->save();
    }

    /**
     * @param int $total
     * @param string $type
     * @param string $maxType
     * 
     * @return int|null
     */
    public function checkBonusExist(int $total, string $type = 'orders', string $maxType = 'orders_bonus')
    {
        return BonusVariable::where($type, '<=', $total)->get()->max($maxType);
    }

    /**
     * @param string $type
     * 
     * @return int
     */
    private function getDriverOrders($type = self::DAY)
    {
        $query = Order::where('status', Order::STATUS_DELIVERED)->where('driver_id', $this->driver->id)->whereYear('created_at', now()->format('Y'));
        
        if ($type == self::DAY) {
            $query->whereDay('created_at', now()->format('d') );
        } else {
            $query->whereMonth('created_at', now()->format('m') );
        }

        return $query->get();
    }

}