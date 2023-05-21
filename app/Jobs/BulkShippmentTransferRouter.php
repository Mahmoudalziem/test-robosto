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
use Webkul\Shipping\Models\ShippmentBulkTransfer;
use Webkul\Shipping\Repositories\ShippmentBulkTransferRepository;
use Webkul\Shipping\Repositories\ShippmentRepository;

class BulkShippmentTransferRouter implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    public $transfer;
    public $status;



    public function __construct(ShippmentBulkTransfer $transfer , $newStatus)
    {
        $this->transfer = $transfer;
        $this->status = $newStatus;

    }


    public function handle(ShippmentBulkTransferRepository $shippmentBulkTransferRepository)
    {
        Log::info('start routing bulk shippment transfer -> ' . $this->transfer->id);
        $shippmentBulkTransferRepository->performOnBulkTransfer($this->transfer , $this->status);
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
