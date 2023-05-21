<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Webkul\Collector\Repositories\CollectorRepository;
use Illuminate\Support\Facades\Log;

class EndInventoryControl implements ShouldQueue {

    use Dispatchable,
        InteractsWithQueue,
        Queueable,
        SerializesModels;

    protected $data;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data) {
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(CollectorRepository $collectorRepository) {

        Log::info('End Inventory Control -> ' . $this->data['inventory_control']->id);
        $collectorRepository->endInventoryControl($this->data);
    }

}
