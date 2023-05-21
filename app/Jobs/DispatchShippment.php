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

class DispatchShippment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    public $shippment;
    public $trial;



    public function __construct(Shippment $shippment , $trial)
    {
        $this->shippment = $shippment;
        $this->trial = $trial;
    }


    public function handle(ShippmentRepository $shippmentRepository)
    {
        Log::info('start shippment order job -> ' . $this->shippment->id);
        $shippmentRepository->createShippmentOrder($this->shippment,$this->trial);
    
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
