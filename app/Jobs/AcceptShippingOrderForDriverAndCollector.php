<?php

namespace App\Jobs;

use Throwable;
use Illuminate\Bus\Queueable;
use Webkul\Sales\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Webkul\Sales\Repositories\OrderRepository;
use GuzzleHttp\Exception\InvalidArgumentException;

class AcceptShippingOrderForDriverAndCollector implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * OrderRepository object
     *
     * @var Order
     */
    public $order;

    /**
     * bickup
     *
     * @var bool
     */
    protected $bickup;


    /**
     * Create a new job instance.
     *
     * @param Order $order
     * @param array $warehouses
     */
    public function __construct(Order $order, bool $bickup)
    {
        $this->order = $order;
        $this->bickup = $bickup;
    }

    /**
     * Execute the job.
     *
     * @param OrderRepository $orderRepository
     * @return void
     */
    public function handle(OrderRepository $orderRepository)
    {
        Log::info('>>>>>>>>>> Accept SHIPPING Order By Default Driver  Job -> ' . $this->order->id);
        $orderRepository->acceptShippingOrderForDriverAndCollector($this->order, $this->bickup);
    }


    /**
     * Handle a job failure.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(Throwable $exception)
    {
        Log::info("Job Failed");
        Log::info($exception);
    }
}
