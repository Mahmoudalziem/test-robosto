<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Webkul\Sales\Models\Order;
use Illuminate\Support\Facades\Log;
use Webkul\Customer\Models\Customer;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Webkul\Customer\Services\Calls\CallCustomer;
use LaravelFCM\Message\Exceptions\InvalidOptionsException;

class CallCustomerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const ORDER_WAITING_TYPE = 'waiting_order';

    /**
     * Order object
     *
     * @var Order
     */
    public $order;

    /**
     * Customer object
     *
     * @var Customer
     */
    public $customer;


    /**
     *
     * @var string
     */
    public $type;

    /**
     * Create a new job instance.
     *
     * @param array $customer
     */
    public function __construct(Customer $customer, Order $order = null, string $type = null)
    {
        $this->order = $order;
        $this->customer = $customer;
        $this->type = $type;
    }

    /**
     * Execute the job.
     *
     * @param CallCustomer $callCustomer
     * @return void
     * @throws InvalidOptionsException
     */
    public function handle(CallCustomer $callCustomer)
    {
        Log::info('Start Call Customer Job -> ' . $this->customer->id);

        if ($this->type == self::ORDER_WAITING_TYPE) {
            Log::info("Call becuase of waiting order for customer response");
            $callCustomer->orderWaitingCall($this->customer, $this->order);
        }

    }
}
