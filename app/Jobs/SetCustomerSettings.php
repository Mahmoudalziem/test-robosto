<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Webkul\Customer\Models\Customer;
use Webkul\Customer\Repositories\CustomerRepository;
use Webkul\Sales\Models\Order;

class SetCustomerSettings implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * OrderRepository object
     *
     * @var Order
     */
    public $customer;

    /**
     * Create a new job instance.
     *
     * @param Order $customer
     */
    public function __construct(Customer $customer)
    {
        $this->customer = $customer;
    }
    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(CustomerRepository $customerRepository)
    {
        Log::info('Start Set Customer Settings customer Processing Job -> ' . $this->customer->id);
        $customerRepository->setCustomerSettings($this->customer);
    }
}
