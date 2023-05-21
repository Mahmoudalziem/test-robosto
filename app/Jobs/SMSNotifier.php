<?php

namespace App\Jobs;

use Throwable;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Webkul\Core\Models\SmsCampaign;
use Webkul\User\Models\Notification;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use LaravelFCM\Message\Exceptions\InvalidOptionsException;
use Webkul\Admin\Http\Controllers\SMSCampaign\SMSCampaignController;

class SMSNotifier implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Notification object
     *
     * @var SmsCampaign
     */
    public $smsCampaign;

    /**
     * Create a new job instance.
     *
     * @param SmsCampaign $notification
     */
    public function __construct(SmsCampaign $smsCampaign)
    {
        $this->smsCampaign = $smsCampaign;
    }

    /**
     * Execute the job.
     *
     * @param SmsCampaignController $smsCampaignController
     * @return void
     * @throws InvalidOptionsException
     */
    public function handle(SMSCampaignController $smsCampaignController)
    {
        Log::info('Start SMS Notifier -> ' . $this->smsCampaign->id);

        $smsCampaignController->sendToNotifiers($this->smsCampaign);
    }

    /**
     * Handle a job failure.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(Throwable $exception)
    {
        Log::info("SMS Job Failed");
        sendSMSToDevTeam("في مشكلة حصلت في الجوب اللي بتبعت كامبين الرسائل SMS ورقمها هو " . $this->smsCampaign->id);
    }
}
