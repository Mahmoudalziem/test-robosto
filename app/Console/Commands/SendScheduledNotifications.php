<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Jobs\PublishNotifications;
use Illuminate\Support\Facades\Log;
use Webkul\User\Models\Notification;

class SendScheduledNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scheduled:notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start Send Scheduled Notifications';

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
        // Get Shceduled Notifications before 30 minutes
        $notifications = Notification::whereNotNull('scheduled_at')
            ->where('fired', 0)
            ->whereBetween('scheduled_at', [Carbon::now(),  Carbon::now()->addMinutes(30)])
            ->get();
        
        foreach ($notifications as $notification) {            
            
            // Dispatch Job at Notification Scheduled At
            PublishNotifications::dispatch($notification)->delay(Carbon::parse($notification->scheduled_at));
            
            $notification->fired = 1;
            $notification->save();
        }
    }
}
