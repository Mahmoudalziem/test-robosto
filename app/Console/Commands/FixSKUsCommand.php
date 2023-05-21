<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Webkul\Core\Services\FixSKUs\FixSkus;

class FixSKUsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sku:fix';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix Sku quantity in inventory warehouse';

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
    public function handle(FixSkus $fixSkus)
    {
        $fixSkus->startFix();

    }
}
