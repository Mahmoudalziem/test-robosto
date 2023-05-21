<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Webkul\Core\Services\Warehouse\StockValue;

class WarehouseStockValueCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'warehouse:stockValue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Warehouse Stock Value Daily';

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
    public function handle(StockValue $stockValue)
    {
        $stockValue->startBuild();

    }
}
