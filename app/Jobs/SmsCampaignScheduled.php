<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Customer\Http\Controllers\Auth\SMSTrait;
use Webkul\Admin\Repositories\SmsCampaign\SmsCampaignRepository;

class SmsCampaignScheduled implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use SMSTrait;
    /**
     * OrderRepository object
     *
     * @var Order
     */
    public $data;

    /**
     * Create a new job instance.
     *
     * @param Order $order
     */
    public function __construct(  $data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @param OrderRepository $orderRepository
     * @return void
     */
    public function handle(SmsCampaignRepository $smsCampaignRepository)
    {
        Log::info('start_job');
        $smsCampaignRepository->smsCampaignScheduled($this->data);
    }
}
