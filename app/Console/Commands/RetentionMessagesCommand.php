<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Webkul\Core\Services\RetentionMessages\RetentionMessages;

class RetentionMessagesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'retention:messages';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automation Give Gifts for the customers';

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
    public function handle(RetentionMessages $retentionMessages)
    {
        Log::info("Start Retention Messages Command");

       $retentionMessages->dispatchRetention();

    }
}
