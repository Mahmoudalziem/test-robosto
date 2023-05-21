<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Webkul\Core\Http\Controllers\BackendBaseController;
use LaravelFCM\Message\Exceptions\InvalidOptionsException;

class TriggerFCMRTDBJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     *
     * @var string
     */
    public $reference;
    
    /**
     *
     * @var mixed
     */
    public $value;
    
    /**
     * Model object
     *
     * @var Model
     */
    public $model;
    
    /**
     * @param string $reference
     * @param $value
     */
    public function __construct(string $reference, $value)
    {
        $this->reference = $reference;
        $this->value = $value;
    }

    /**
     * Execute the job.
     *
     * @param BackendBaseController $backendBaseController
     * @return void
     * @throws InvalidOptionsException
     */
    public function handle(BackendBaseController $backendBaseController)
    {
        Log::info('Start FCM Realtime DB Job -> ' . $this->value);
        $backendBaseController->triggerFCMDB($this->reference, $this->value);
    }
}
