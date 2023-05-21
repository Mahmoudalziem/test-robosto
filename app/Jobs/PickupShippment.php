<?php

namespace App\Jobs;

use Throwable;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Webkul\Shipping\Models\Shippment;
use Webkul\Shipping\Repositories\ShippmentRepository;

class PickupShippment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    public $shippment_id;



    public function __construct($shippment_id)
    {
        $this->shippment_id = $shippment_id;
    }


    public function handle(ShippmentRepository $shippmentRepository)
    {
        Log::info('start pickup job -> ' . $this->shippment_id);
        $shippment = Shippment::find($this->shippment_id);
        if($shippment){
            $shippmentRepository->createPickUpOrder($shippment);
        }else{
            PickupShippment::dispatch($this->shippment_id);
        }

    }


    /**
     * Handle a job failure.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(Throwable $exception)
    {
        Log::info("Job Failed");
        Log::info($exception);
    }
}
