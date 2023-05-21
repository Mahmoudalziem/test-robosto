<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel {

    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        'App\Console\Commands\TrackingScheduledOrders',
        'App\Console\Commands\SendScheduledNotifications',
        'App\Console\Commands\SendScheduledSmsCampaigns',
        'App\Console\Commands\PermissionsBuild',
        'App\Console\Commands\RetentionMessagesCommand',
        'App\Console\Commands\FixSKUsCommand',
        'App\Console\Commands\WarehouseStockValueCommand',
        'App\Console\Commands\LogoutAllDriversCommand',
        'App\Console\Commands\ResetDriverSupervisorRate',
        'App\Console\Commands\BNPLTransactionsCommand',
        'App\Console\Commands\AreaWarehouseAdjuster',
        'App\Console\Commands\AreaTotalQuantityAdjuster',
        // 'App\Console\Commands\DriverCalculateOrdersAndWorkingTime',
    ];

    /**
     * Define the application's command schedule.
     *
     * @param Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule) {
        $schedule->command('orders:trackingScheduled')->hourly();
        $schedule->command('scheduled:notifications')->everyMinute();
        $schedule->command('scheduled:smsCampaigns')->everyMinute();
        $schedule->command('retention:messages')->dailyAt('11:00');
        $schedule->command('sku:fix')->hourly();
        $schedule->command('warehouse:stockValue')->dailyAt('3:00');
        $schedule->command('drivers:logout')->hourly();
        $schedule->command('supervisor:reset')->monthlyOn(1, '1:00');
        $schedule->command('bnpl:check')->dailyAt('01:00');
        // $schedule->command('drivers:bonus')->monthlyOn(1, '1:00');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands() {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }

}
