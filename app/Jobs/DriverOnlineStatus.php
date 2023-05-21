<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Webkul\Driver\Repositories\DriverRepository;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
class DriverOnlineStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $driverRepository;
    public function __construct( $driverRepository)
    {
        $this->driverRepository = $driverRepository;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Allow only 2 emails every 1 second
//        Redis::throttle('any_key')->allow(2)->every(1)->then(function () {
//
//            $recipient = 'hello@example.com';
//           // Mail::to($recipient)->send(new OrderShipped($this->order));
//         //   Log::info('Emailed order ' . $this->order->id);
//            Log::info('Driver Status update inside queue ' . $this->driverRepository->id);
//
//        }, function () {
//            // Could not obtain lock; this job will be re-queued
//            return $this->release(2);
//        });


    }
}
