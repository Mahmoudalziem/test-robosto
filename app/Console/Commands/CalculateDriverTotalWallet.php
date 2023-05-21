<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Webkul\Driver\Models\Driver;
use Spatie\EventSourcing\StoredEvents\Models\EloquentStoredEvent;
use Webkul\Driver\Events\MoneyAdded;

class CalculateDriverTotalWallet extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'drivers:wallet';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Caculate Total Wallet for Drivers';

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
            $total =  EloquentStoredEvent::query()
                ->whereEventClass(MoneyAdded::class)
                ->where('event_properties->driverId', $driver->id)
                ->latest()->get()->sum('event_properties.amount');

            $driver->total_wallet = $total;
            $driver->save();
        }
    }
}
