<?php

namespace App\Jobs;

use Webkul\Core\Models\Tag;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Webkul\Customer\Models\Customer;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Webkul\Admin\Http\Controllers\Customer\CustomerController;


class SendTagSMS implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Customer object
     *
     * @var Customer
     */
    public $customer;

    /**
     * CustomerRepository object
     *
     * @var Array
     */
    public $tags;

    /**
     * Create a new job instance.
     *
     * @param Array $tags
     */
    public function __construct(Customer $customer, array $tags)
    {
        $this->tags     = $tags;
        $this->customer = $customer;
    }


    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(CustomerController $customerController)
    {
        Log::info('Start Send Tag SMS Job');
        $customerController->sendTagSms($this->customer, $this->tags);
    }
}
