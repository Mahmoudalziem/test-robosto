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

class AcceptOrderByDefaultDriver implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * OrderRepository object
     *
     * @var Order
     */
    public $order;

    /**
     * warehouses
     *
     * @var array
     */
    protected $warehouses;


    /**
     * Create a new job instance.
     *
     * @param Order $order
     * @param array $warehouses
     */
    public function __construct(Order $order, array $warehouses)
    {
        $this->order = $order;
        $this->warehouses = $warehouses;
    }

    /**
     * Execute the job.
     *
     * @param OrderRepository $orderRepository
     * @return void
     */
    public function handle(OrderRepository $orderRepository)
    {
        Log::info('>>>>>>>>>> Accept Order By Default Driver  Job -> ' . $this->order->id);
        $orderRepository->acceptOrderByDefaultDriver($this->order, $this->warehouses);
    }


    /**
     * Handle a job failure.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(Throwable $exception)
    {
        Log::info("AcceptOrderByDefaultDriver Job Failed");
        Log::info("Job Failed");
        Log::info($exception);
    }
}
