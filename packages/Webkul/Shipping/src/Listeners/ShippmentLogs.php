<?php

namespace Webkul\Shipping\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Webkul\Shipping\Models\Shippment;
use Webkul\Shipping\Repositories\ShippmentLogsRepository;
use App\Jobs\UpdateShipperInfo;

class ShippmentLogs implements ShouldQueue
{

    protected $shippmentLogsRepository;
    public function __construct(ShippmentLogsRepository $shippmentLogsRepository)
    {
        $this->shippmentLogsRepository = $shippmentLogsRepository;
    }


    public function addShippmentLog(Shippment $shippment, string $logType, $notes = null)
    {
        if($shippment->shipper_id ==4){
            UpdateShipperInfo::dispatch($shippment->shipping_number,$logType,now()->format('Y-m-d H:i:s'))->onQueue('requests-queue');;
        }
        $this->shippmentLogsRepository->create(
            [
                'shippment_id' => $shippment->id,
                'log_type' => $logType,
                'log_time' => now()->format('Y-m-d H:i:s')
            ]
        );
    }
}
