<?php

namespace App\Jobs;

use Throwable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Webkul\Shipping\Repositories\ShippmentRepository;
use Carbon\Carbon;

class UpdateShipperInfo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $shippment;
    public $logType;

    public $updateDate;
    public function __construct($shippment, $logType , $updateDate)
    {
        $this->shippment = $shippment;
        $this->logType = $logType;
        $this->updateDate = $updateDate;
    }

    public function handle(ShippmentRepository $shipmentRepository)
    {
        if (in_array($this->logType, ['picking_up_order_created','items_picked_up','shippment_trial_created', 'shippment_trial_on_the_way', 'delivered', 'picking_up_order_failed', 'returned_to_vendor','shippment_trial_failed', 'shippment_trial_rescheduled', 'failed'])) {
            $this->sendDataToShipper($this->logType, $this->shippment);
        }
    }



    public function failed(Throwable $exception)
    {
        Log::info('shipper error');
        Log::info($exception);

    }

    public function sendDataToShipper($status, $tracking_number)
    {
        $url = 'https://flextock-couriers-webhooks-target-live-xdd4dstqaq-uc.a.run.app/delivery_updates_webhook';
        $headers = [
            'key:gAAAAABkAJeQzfbAsn_r5EgTWEzdWQGiA_b9gfuU-TUMmtSllVUA39z_GkaP0Mxo5cuKvoamwIE4XggeLHvdBrhN0msqe_woxIW9dfz_kbtDy4NxAAvnhaw=',
            'Content-Type: application/json'
        ];
        $data = [
            'status' => $status,
            'timestamp' => $this->updateDate?$this->updateDate:Carbon::now(),
            'shipping_number' => $tracking_number,
            'order_type' => 'outbound'
        ];
        $x = requestWithCurl($url, 'POST', $data, $headers);
        Log::info('shipper success');
        Log::info($data);
        Log::info($x);
    }
}
