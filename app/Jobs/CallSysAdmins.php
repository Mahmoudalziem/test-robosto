<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Webkul\Sales\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use LaravelFCM\Message\Exceptions\InvalidOptionsException;
use Webkul\Sales\Repositories\OrderServices\CallAdmins;

class CallSysAdmins implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Order object
     *
     * @var Order
     */
    public $order;

    /**
     * Create a new job instance.
     *
     * @param Order $order
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Execute the job.
     *
     * @param CallAdmins $callAdmins
     * @return void
     * @throws InvalidOptionsException
     */
    public function handle(CallAdmins $callAdmins)
    {
        Log::info('Start Call Area Manager and Operation Manager -> ' . $this->order->id);
        $callAdmins->callAreaAndOperationManagers($this->order);
    }
}
