<?php

namespace App\Jobs;

use Throwable;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Webkul\User\Models\Notification;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use LaravelFCM\Message\Exceptions\InvalidOptionsException;
use Webkul\Admin\Http\Controllers\Notification\NotificationController;

class NotificationsNotifier implements ShouldQueue
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
        Log::info('Start Notifications Notifier -> ' . $this->notification->id);

        $notificationController->sendToNotifiers($this->notification);
    }


    /**
     * Handle a job failure.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(Throwable $exception)
    {
        Log::info("Notification Job Failed");
        sendSMSToDevTeam("في مشكلة حصلت في الجوب اللي بتبعت كامبين نوتيفيكشن ورقمها هو " . $this->notification->id);
    }
}
