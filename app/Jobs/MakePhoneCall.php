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

class MakePhoneCall implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Order object
     *
     * @var Order
     */
    public $order;

    /**
     * phones
     *
     * @var array
     */
    public $phones;
    
    /**
     * delayTime
     *
     * @var int
     */
    public $delayTime;

    /**
     * Create a new job instance.
     *
     * @param array $phones
     */
    public function __construct(Order $order, array $phones, int $delayTime = null)
    {
        $this->order = $order;
        $this->phones = $phones;
        $this->delayTime = $delayTime;
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
        Log::info('Start Call System Admins Job -> ' . $this->order->id);
        $callAdmins->callPhones($this->order, $this->phones, $this->delayTime);
    }
}
