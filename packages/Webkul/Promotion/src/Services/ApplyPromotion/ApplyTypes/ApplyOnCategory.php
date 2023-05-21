<?php
namespace Webkul\Promotion\Services\ApplyPromotion\ApplyTypes;

use Webkul\Product\Models\Product;
use Webkul\Promotion\Models\Promotion;

class ApplyOnCategory extends ItemsHandling implements Type
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

        // Get Exceptions Items for this Promotion
        $exceptionItems = $this->excludeExceptionsItems($promotion, $items);
        
        // Finally Get Valid items to Apply
        $itemsToApply = $exceptionItems['items_to_apply'];
        // Save Except Items
        $exceptItems = $exceptionItems['except_items'];

        
        // if [ 'All' ]
        if (!$applyType) {
            // Exclude Products if that Promotion Type Not [ All ]
            $unsupportedItems = $this->excludeUnsupportedItems($promotion, $itemsToApply);
            
            // Finally Get Valid items to Apply
            $itemsToApply = $unsupportedItems['items_to_apply'];
            
            // Save Except Items
            $exceptItems = array_merge($exceptItems, $unsupportedItems['except_items']);
        }

        // Finally Apply Promotion on Valid Items if Exist
        $newItems = [];
        if (count($itemsToApply) != 0) {
            $newItems = $this->applyPromotionOnItems($promotion, $itemsToApply);
        }

        $formattedItems = $this->reformateItems($items, $newItems, $exceptItems);

        return [
            'discounted_items' =>  $formattedItems['discounted_items'],
            'except_items' =>  $formattedItems['except_items'],
        ];
    }


    /**
     * Apply Promotion On Som Items
     */
    public function excludeUnsupportedItems(Promotion $promotion, array $itemsToApply)
    {
        $promotionCategories = $promotion->categories->pluck('category_id')->toArray();
        $products = $this->getProductsFromDB($itemsToApply);
        
        $exceptionItems = [];

        foreach ($itemsToApply as $key => $value) {
            // Get Product
            $product = $products->where('id', $value['id'])->first();

            // Get Product SubCategories
            $productCategoriesIDs = $this->getProductCategory($product);

            // Check Product in Promotion Categories
            if ( count(array_intersect($productCategoriesIDs, $promotionCategories)) == 0) {
                $exceptionItems[] = $value;
                unset($itemsToApply[$key]);
            }
        }

        return ['except_items'  =>   $exceptionItems, 'items_to_apply'  => $itemsToApply];
    }


    /**
     * Get Product SubCategories
     */
    private function getProductCategory($product)
    {
        $productCategories = [];
        foreach ($product->subCategories as $subCategory) {
            $productCategories = array_merge($productCategories, $subCategory->parentCategories->pluck('id')->toArray());
        }

        return $productCategories;
    }

}