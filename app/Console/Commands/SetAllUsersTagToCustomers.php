<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Webkul\Core\Models\Tag;
use Illuminate\Console\Command;
use App\Jobs\PublishNotifications;
use Illuminate\Support\Facades\Log;
use Webkul\Customer\Models\Customer;
use Webkul\User\Models\Notification;

class SetAllUsersTagToCustomers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'customers:tags';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start Set All Users Tag To Customers';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        foreach (Customer::all() as $customer) {
            $customerTags = $customer->tags->pluck('id')->toArray();

            if (!in_array(Tag::ALL_USERS, $customerTags)) {
                $customer->tags()->attach(Tag::ALL_USERS);
            }
        }
    }
}
