<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Database\Eloquent\Collection;
use Webkul\Admin\Http\Controllers\Customer\CustomerController;


class CustomersRetentionMessages implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Customer object
     *
     * @var Collection
     */
    public $customers;

    /**
     * CustomerRepository object
     *
     * @var Array
     */
    public $tags;

    /**
     * Create a new job instance.
     *
     * @param Collection $customers
     * @param array $tags
     */
    public function __construct(Collection $customers, array $tags)
    {
        $this->tags     = $tags;
        $this->customers = $customers;
    }


    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(CustomerController $customerController)
    {
        Log::info('Start Retention Message Job');
        $customerController->retentionMessage($this->customers, $this->tags);
    }
}
