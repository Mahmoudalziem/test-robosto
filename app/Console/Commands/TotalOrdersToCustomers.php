<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Webkul\Core\Models\Tag;
use Illuminate\Console\Command;
use App\Jobs\PublishNotifications;
use Illuminate\Support\Facades\Log;
use Webkul\Customer\Models\Customer;
use Webkul\Sales\Models\Order;
use Webkul\User\Models\Notification;

class TotalOrdersToCustomers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'customers:orders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start Set Total orders To Customers';

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
            
            $orders = Order::where('customer_id', $customer->id)->get();
            $deliveredOrders = $orders->where('status', Order::STATUS_DELIVERED)->count();
            $totalOrders = $orders->count();
            
            $customer->total_orders = $totalOrders;
            $customer->delivered_orders  = $deliveredOrders;
            $customer->save();
        }
    }
}
