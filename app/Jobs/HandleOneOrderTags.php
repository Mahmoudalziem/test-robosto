<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Webkul\Sales\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Webkul\Admin\Http\Controllers\Customer\CustomerController;

class HandleOneOrderTags implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * OrderRepository object
     *
     * @var Order
     */
    public $order;

    /**
     * Create a new job instance.
     *
     * @param Order $order
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Execute the job.
     *
     * @param CustomerController $customerController
     * @return void
     */
    public function handle(CustomerController $customerController)
    {
        if ($this->order->promotion) {
            
            Log::info('Start handle One Order Tag Job -> ');

            $customerController->handleOneOrderTag($this->order->promotion, $this->order->customer);
        }

    }
}
