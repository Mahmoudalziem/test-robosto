<?php
namespace Webkul\Promotion\Services\ApplyPromotion\ApplyTypes;

use Webkul\Product\Models\Product;
use Webkul\Promotion\Models\Promotion;
use Illuminate\Support\Facades\Log;

class ApplyOnProduct extends ItemsHandling implements Type
{

    /**
     * Apply Promotion Type
     *
     * @param Promotion $promotion
     * @param Array $items
     */
    public function apply(Promotion $promotion, array $items)
    {
        // Get Apply Type [ null | 'all' ]
        $applyType = $promotion->apply->model_type;
        Log::info('apply on before');
        Log::info($items);
        // Get Exceptions Items for this Promotion
        $exceptionItems = $this->excludeExceptionsItems($promotion, $items);

        // Finally Get Valid items to Apply
        $itemsToApply = $exceptionItems['items_to_apply'];

        // Save Except Items
        $exceptItems = $exceptionItems['except_items'];


        // if [ Not => 'All' ]
        if (!$applyType) {
            // Exclude Products if that Promotion Type Not [ All ]
            $unsupportedItems = $this->excludeUnsupportedItems($promotion, $itemsToApply);

            // Finally Get Valid items to Apply
            $itemsToApply = $unsupportedItems['items_to_apply'];

            // Save Except Items
            $exceptItems = array_merge($exceptItems, $unsupportedItems['except_items']);
        }
        Log::info('apply on products');
        Log::info($itemsToApply);
        // Finally Apply Promotion on Valid Products
        $newItems = $this->applyPromotionOnItems($promotion, $itemsToApply);

        $formattedItems = $this->reformateItems($items, $newItems, $exceptItems);

        return [
            'discounted_items' =>  $formattedItems['discounted_items'],
            'except_items' =>  $formattedItems['except_items'],
        ];
    }


    /**
     * Apply Promotion On Som Items
     */
    public function excludeUnsupportedItems(Promotion $promotion, array $givenItems)
    {
        $promotionProducts = $promotion->products->pluck('product_id')->toArray();
        $exceptionItems = [];

        foreach ($givenItems as $key => $value) {

            // Check Product in Promotion Products
            if ( !in_array($value['id'], $promotionProducts) ) {
                $exceptionItems[] = $value;
                unset($givenItems[$key]);
            }
        }

        return ['except_items'  =>   $exceptionItems, 'items_to_apply'  => $givenItems];
    }
}