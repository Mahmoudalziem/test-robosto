<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Webkul\Core\Services\BNPLTransactions\BNPLTransactions;

class BNPLTransactionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bnpl:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'check for due bnpl payments';

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
    public function handle(BNPLTransactions $bNPLTransactions)
    {
        Log::info("Start applying BNPL Command");
       $bNPLTransactions->applyBNPLPayments();

    }
}
