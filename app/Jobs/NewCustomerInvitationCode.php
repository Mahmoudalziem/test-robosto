<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Webkul\Customer\Models\Customer;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Webkul\Customer\Repositories\CustomerRepository;

class NewCustomerInvitationCode implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * CustomerRepository object
     *
     * @var Customer
     */
    public $customer;
    
    /**
     * Array 
     *
     * @array $data
     */
    public $data;

    /**
     * Create a new job instance.
     *
     * @param Customer $customer
     */
    public function __construct(Customer $customer, array $data)
    {
        $this->customer = $customer;
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @param CustomerRepository $customerRepository
     * @return void
     */
    public function handle(CustomerRepository $customerRepository)
    {
        $customerRepository->handleInvitaionCode($this->customer, $this->data);
    }
}
