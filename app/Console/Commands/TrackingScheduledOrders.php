<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Webkul\Sales\Models\Order;
use App\Jobs\TrackingScheduledOrders as TrackingScheduledOrdersJob;
class TrackingScheduledOrders extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:trackingScheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tracking Scheduled Orders!';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    protected $notify;

    public function __construct() {
        parent::__construct();

    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {


        $orders = Order::
            where('status', Order::STATUS_SCHEDULED )
            ->where('in_queue',0)
            ->whereBetween('scheduled_at', [  Carbon::now()->subMinutes(5),  Carbon::now()->addHours(2)])
            ->whereNull('shippment_id')
            ->get();

        foreach($orders as $order){
            // update order in_queue =1 order save
            $order->in_queue= 1;
            $order->save();
            TrackingScheduledOrdersJob::dispatch($order)
                                        ->delay(Carbon::parse($order->scheduled_at)->subMinutes(config('robosto.SCHEDULED_ORDER_COMMAND')));
        }

    }

}
