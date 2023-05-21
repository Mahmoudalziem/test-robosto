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
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Customer\Repositories\CustomerRepository;

class HandleCustomerInvitationInOrder implements ShouldQueue
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
     * @param CustomerRepository $customerRepository
     * @return void
     */
    public function handle(CustomerRepository $customerRepository)
    {
        Log::info('Start handle Customer Invitaion Code Job -> ');

        // Get Order Customer
        $customer = $this->order->customer;

        // check if this invitation applied
        if ($customer->invitation_applied == 1) {
            return false;
        }

        // Process the job
        $customerRepository->addMoneyToReferralCodeOwner($customer, $this->order);
    }
}
