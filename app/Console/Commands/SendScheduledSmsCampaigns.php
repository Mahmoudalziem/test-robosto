<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Jobs\PublishSmsCampaign;
use Illuminate\Support\Facades\Log;
use Webkul\Core\Models\SmsCampaign;

class SendScheduledSmsCampaigns extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scheduled:smsCampaigns';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start Send Scheduled smsCampaigns';

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
        // Get Shceduled smsCampaigns before 30 minutes
        $smsCampaigns = SmsCampaign::whereNotNull('scheduled_at')
            ->where('is_pushed', 0)
            ->whereBetween('scheduled_at', [Carbon::now(),  Carbon::now()->addMinutes(30)])
            ->get();
        
        foreach ($smsCampaigns as $smsCampaigns) {            
            
            // Dispatch Job at  smsCampaign  Scheduled At
            PublishSmsCampaign::dispatch($smsCampaigns)->delay(Carbon::parse($smsCampaigns->scheduled_at));
            
            $smsCampaigns->is_pushed = 1;
            $smsCampaigns->save();
        }
    }
}
