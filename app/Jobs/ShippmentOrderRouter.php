<?php

namespace App\Jobs;

use Throwable;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Webkul\Sales\Models\Order;
use Webkul\Shipping\Models\Shippment;
use Webkul\Shipping\Repositories\ShippmentRepository;

class ShippmentOrderRouter implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    public $order;
    public $config;


    public function __construct(Order $order,$config=[])
    {
        $this->order = $order;
        $this->config = $config;
    }


    public function handle(ShippmentRepository $shippmentRepository)
    {
        Log::info('start routing shippment job -> ' . $this->order->id);
        $shippmentRepository->routeShippment($this->order , $this->config);
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
