<?php

namespace Webkul\Driver\Listeners;

use Webkul\Driver\Models\Driver;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldQueue;
use Webkul\Driver\Services\CalculateWorkingBonus;
use Webkul\Driver\Repositories\WorkingCycleRepository;

class DriverBonus implements ShouldQueue
{
    protected $workingCycleRepository;

    /**
     * @param int $driverId
     * 
     * @return void
     */
    public function calculateNumberOfOrders(int $driverId)
    {
        Log::info('Start Calculate Number Of Orders Listner');

        $calculateWorkingBonus = new CalculateWorkingBonus(Driver::find($driverId));
        $calculateWorkingBonus->calculateNumberOfOrders();
        $calculateWorkingBonus->runTheEquation();
    }
    
    
    /**
     * @param int $driverId
     * 
     * @return void
     */
    public function calculateNumberOfWorkingHours(int $driverId)
    {
        Log::info('Start Calculate Number Of Working Hours Listner');

        $calculateWorkingBonus = new CalculateWorkingBonus(Driver::find($driverId));
        $calculateWorkingBonus->calculateNumberOfWorkingHours();
        $calculateWorkingBonus->runTheEquation();
    }
    
    
    /**
     * @param int $driverId
     * 
     * @return void
     */
    public function calculateCustomersRating(int $driverId)
    {
        Log::info('Start Calculate Customer Rating Listner');

        $calculateWorkingBonus = new CalculateWorkingBonus(Driver::find($driverId));
        $calculateWorkingBonus->calculateCustomersRating();
        $calculateWorkingBonus->runTheEquation();
    }


    /**
     * @param int $driverId
     * 
     * @return void
     */
    public function calculateSupervisorRating(int $driverId)
    {
        Log::info('Start Calculate Supervisor Rating Listner');

        $calculateWorkingBonus = new CalculateWorkingBonus(Driver::find($driverId));
        $calculateWorkingBonus->calculateSupervisorRating();
        $calculateWorkingBonus->runTheEquation();
    }
    
    
    /**
     * @param int $driverId
     * 
     * @return void
     */
    public function calculateBackBonus(int $driverId)
    {
        Log::info('Start Calculate Back Bonus Listner');

        $calculateWorkingBonus = new CalculateWorkingBonus(Driver::find($driverId));
        $calculateWorkingBonus->calculateBackBonus();
        $calculateWorkingBonus->runTheEquation();
    }
    
    
    /**
     * @param int $driverId
     * 
     * @return void
     */
    public function calculateWorkingPathRating(int $driverId)
    {
        Log::info('Start Calculate Working Path Cycle Listner');

        $calculateWorkingBonus = new CalculateWorkingBonus(Driver::find($driverId));
        $calculateWorkingBonus->calculateWorkingPathRating();
        $calculateWorkingBonus->runTheEquation();
    }

}
