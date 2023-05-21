<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Webkul\Driver\Models\Driver;
use Webkul\Driver\Repositories\DriverRepository;

class AssignNewOrdersToDriver implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * DriverRepository object
     *
     * @var Driver
     */
    public $driver;

    /**
     * Create a new job instance.
     *
     * @param Driver $driver
     */
    public function __construct(Driver $driver)
    {
        $this->driver = $driver;
    }
    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(DriverRepository $driverRepository)
    {
        Log::info('Start Assign New Orders On The Driver -> ' . $this->driver->id);
        $driverRepository->assignNewOrdersToDriver($this->driver);
    }
}
