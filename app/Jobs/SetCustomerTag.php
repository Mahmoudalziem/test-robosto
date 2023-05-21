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

class SetCustomerTag implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * CustomerRepository object
     *
     * @var Customer
     */
    public $customer;

    /**
     * Create a new job instance.
     *
     * @param Customer $customer
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
        Log::info('Start Set Customer Tag customer Processing Job -> ' . $this->customer->id);
        $customerRepository->setCustomerTag($this->customer);
    }
}
