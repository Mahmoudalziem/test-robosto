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
use Webkul\Sales\Models\PaymentMethod;
use Webkul\Sales\Models\OrderPayment;

class PayViaCreditCard implements ShouldQueue {

    use Dispatchable,
        InteractsWithQueue,
        Queueable,
        SerializesModels;

    /**
     * OrderRepository object
     *
     * @var Order
     */
    public $order;

    /**
     * Create a new job instance.
     *
     * @param Order $order
     */
    public function __construct(Order $order) {
        $this->order = $order;
    }

    /**
     * Execute the job.
     *
     * @param OrderRepository $orderRepository
     * @return void
     */
    public function handle(OrderRepository $orderRepository) {
        Log::info('Pay Via Credit Card in worker -> ' . $this->order->id . '----->' . (($this->order->status != Order::STATUS_PREPARING && $this->order->status != Order::STATUS_PENDING) || $this->order->is_paid == Order::ORDER_PAID ));
        if (($this->order->status != Order::STATUS_PREPARING && $this->order->status != Order::STATUS_PENDING) || $this->order->is_paid == Order::ORDER_PAID) {
            return false;
        }

        Log::info('Pay Via Credit Card in worker -> ' . $this->order->id);
        $orderRepository->payOrderPriceViaCC($this->order);
    }

    /**
     * Handle a job failure.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(Throwable $exception) {
        Log::info("Pay Via CC Job Failed : message is >>> " . $exception->getMessage());
        Log::info("Convert The Order To COD");
 
        // Convert Order To Cache On Delivery [ COD ]
        $payment = PaymentMethod::where('slug', OrderPayment::CASH_ON_DELIVERY)->first();
        $order = $this->order;
        $order->payment()->update([
            'method' => $payment->slug,
            'payment_method_id' => $payment->id,
            'paymob_card_id' => null
        ]);
    }

}
