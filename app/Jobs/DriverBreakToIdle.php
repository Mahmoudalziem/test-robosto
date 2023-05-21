<?php

namespace App\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Webkul\Driver\Models\Driver;
use Illuminate\Support\Facades\Log;
use App\Jobs\AssignNewOrdersToDriver;
use Illuminate\Support\Facades\Redis;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Webkul\Driver\Repositories\DriverRepository;

class DriverBreakToIdle implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $driver;
    public function __construct( Driver $driver)
    {
        $this->driver = $driver;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle( DriverRepository $driverRepository)
    {
        $driverRepository->setStatusIdle( $this->driver);

        Log::info('From Break-To-IDLE Function ==> Assign New Orders On The Driver -> ' . $this->driver->id);

        AssignNewOrdersToDriver::dispatch($this->driver)->delay(Carbon::now()->addSeconds(5));

    }
}
