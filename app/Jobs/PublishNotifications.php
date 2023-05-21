<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use LaravelFCM\Message\Exceptions\InvalidOptionsException;
use Webkul\Admin\Http\Controllers\Notification\NotificationController;
use Webkul\User\Models\Notification;

class PublishNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Notification object
     *
     * @var Notification
     */
    public $notification;

    /**
     * Create a new job instance.
     *
     * @param Notification $notification
     */
    public function __construct(Notification $notification)
    {
        $this->notification = $notification;
    }

    /**
     * Execute the job.
     *
     * @param NotificationController $notificationController
     * @return void
     * @throws InvalidOptionsException
     */
    public function handle(NotificationController $notificationController)
    {
        Log::info('Start Send Marketing Notification to Customers ');
        $notificationController->publishNotifications($this->notification);
    }
}
