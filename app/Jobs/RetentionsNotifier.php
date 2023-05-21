<?php

namespace App\Jobs;

use Throwable;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Webkul\Core\Models\RetentionMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use LaravelFCM\Message\Exceptions\InvalidOptionsException;
use Webkul\Admin\Http\Controllers\Customer\CustomerController;

class RetentionsNotifier implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Notification object
     *
     * @var RetentionMessage
     */
    public $retentionMessage;

    /**
     * Create a new job instance.
     *
     * @param RetentionMessage $retentionMessage
     */
    public function __construct(RetentionMessage $retentionMessage)
    {
        $this->retentionMessage = $retentionMessage;
    }

    /**
     * Execute the job.
     *
     * @param CustomerController $customerController
     * @return void
     * @throws InvalidOptionsException
     */
    public function handle(CustomerController $customerController)
    {
        Log::info('Start Retention Notifier -> ' . $this->retentionMessage->id);

        $customerController->sendToNotifiers($this->retentionMessage);
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
        sendSMSToDevTeam("في مشكلة حصلت في الجوب اللي بتبعت رسائل الريتنشن ورقمها هو " . $this->retentionMessage->id);
    }
}
