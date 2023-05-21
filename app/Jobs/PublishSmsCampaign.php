<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Webkul\User\Models\Notification;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use LaravelFCM\Message\Exceptions\InvalidOptionsException;
use Webkul\Core\Models\SmsCampaign;
use Webkul\Admin\Http\Controllers\SMSCampaign\SMSCampaignController;

class PublishSmsCampaign implements ShouldQueue
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
        Log::info('Start Send Marketing Sms Campaign to Customers ');
        $smsCampaignController->publishSmsCampaigns($this->smsCampaign);
    }
}
