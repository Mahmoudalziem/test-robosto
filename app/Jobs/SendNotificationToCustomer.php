<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use LaravelFCM\Message\Exceptions\InvalidOptionsException;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Repositories\OrderRepository;

class SendNotificationToCustomer implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

   /**
     * OrderRepository object
     *
     * @var Order
     */
    public $order;

    /**
     * $data
     *
     * @var array
     */
    public $data;

    /**
     * Create a new job instance.
     *
     * @param Order $order
     */
    public function __construct(Order $order, array $data)
    {
        $this->order = $order;
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @param OrderRepository $orderRepository
     * @return void
     * @throws InvalidOptionsException
     */
    public function handle(OrderRepository $orderRepository)
    {
        Log::info('Start Send Notification to Customer -> ');
        $orderRepository->sendNotificationToCustomer($this->order, $this->data);
    }
}
