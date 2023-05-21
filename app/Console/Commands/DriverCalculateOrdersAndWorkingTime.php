<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Webkul\Driver\Models\Driver;
use Webkul\Driver\Services\CalculateOrdersAndWorkingTime;
use Webkul\Driver\Services\CalculateWorkingBonus;

class DriverCalculateOrdersAndWorkingTime extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'drivers:bonus';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start Calculate Driver Orders And Working Time';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(CalculateOrdersAndWorkingTime $calculateOrdersAndWorkingTime)
    {
        $drivers = Driver::where('id', '>', 4)->get();
        foreach ($drivers as $driver) {
            $calculateOrdersAndWorkingTime->startCalculate($driver);

            $calculateWorkingBonus = new CalculateWorkingBonus($driver);
            $calculateWorkingBonus->runTheEquation();
        }
    }
}
