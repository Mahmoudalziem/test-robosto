<?php

namespace Webkul\Driver\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Webkul\Driver\Models\Driver;
use Webkul\Driver\Repositories\DriverRepository;

class DriverCreateBefore
{

    protected $driverRepository;
    public function __construct(
        DriverRepository $driverRepository
    )
    {
        $this->driverRepository = $driverRepository;

    }
    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle( )
    {

        Log::info(request()->all() );
    }
}
