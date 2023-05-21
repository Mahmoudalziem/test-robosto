<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Webkul\Driver\Models\Driver;
use Spatie\EventSourcing\StoredEvents\Models\EloquentStoredEvent;
use Webkul\Driver\Events\MoneyAdded;

class ResetDriverSupervisorRate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'supervisor:reset';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset Driver Supervisor Rate';

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
    public function handle()
    {
        foreach (Driver::all() as $driver) {
            $driver->supervisor_rate = null;
            $driver->save();
        }
    }
}
