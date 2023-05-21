<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Webkul\Promotion\Models\Promotion;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use LaravelFCM\Message\Exceptions\InvalidOptionsException;
use Webkul\Admin\Repositories\Promotion\PromotionRepository;

class StorePromotionExceptions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Promotion object
     *
     * @var Promotion
     */
    public $promotion;
    
    /**
     * Create a new job instance.
     *
     * @param Promotion $promotion
     */
    public function __construct(Promotion $promotion)
    {
        $this->promotion = $promotion;
    }

    /**
     * Execute the job.
     *
     * @param PromotionRepository $promotionRepository
     * @return void
     * @throws InvalidOptionsException
     */
    public function handle(PromotionRepository $promotionRepository)
    {
        Log::info('Start Promotion Exceptions Job -> ' . $this->promotion->id);
        $promotionRepository->savePromotionExceptions($this->promotion);
    }
}
