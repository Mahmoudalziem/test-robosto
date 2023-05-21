<?php

namespace Webkul\Promotion\Services\ApplyPromotion\ApplyTypes;

use Webkul\Product\Models\Product;
use Webkul\Promotion\Models\Promotion;
use Illuminate\Support\Facades\Log;

class ItemsHandling
{
    /**
     * Apply Promotion On All Items
     */
    protected function excludeExceptionsItems(Promotion $promotion, array $items)
    {

        //get offers items 
        $categories = config('robosto.EXCLUDED_CATEGORIES');
        $excludedProductsFromDB = Product::whereHas('subCategories', function ($q) use ($categories) {
                        $q->whereHas('parentCategories', function ($q2) use ($categories) {
                            $q2->whereIn('categories.id', $categories);
                        });
        })->pluck('id')->toArray();


        // Get Exception Items IDs
        // $getExceptionItems = $promotion->exceptions_items;
        $getExceptionItems = $promotion->exceptionProducts->isNotEmpty() ? $promotion->exceptionProducts->pluck('product_id')->toArray() : null;
        $exceptionItems = [];

        $itemsToApply = $items;
        if (is_array($getExceptionItems)) {
            // Remove Exception Items
            foreach ($itemsToApply as $key => $value) {
                if (in_array($value['id'], $getExceptionItems)) {
                    $exceptionItems[] = $value;
                    unset($itemsToApply[$key]);
                }
            }
        }

        if (is_array($excludedProductsFromDB)) {
            // Remove Exception Items in offers
            foreach ($itemsToApply as $key => $value) {
                if (in_array($value['id'], $excludedProductsFromDB)) {
                    $exceptionItems[] = $value;
                    unset($itemsToApply[$key]);
                }
            }
        }

        return ['except_items'  =>   $exceptionItems, 'items_to_apply'  => $itemsToApply];
    }

    /**
     * Apply Promotion On Items
     */
    protected function applyPromotionOnItems(Promotion $promotion, array $items)
    {
        if ($promotion->price_applied == Promotion::PRICE_APPLIED_ORIGINAL) {

            return $this->makeApplyOnOriginalPrice($promotion, $items);
        } else {

            return $this->makeApplyOnDiscountedPrice($promotion, $items);
        }
    }


    /**
     * Apply Promotion Using Original Price
     */
    protected function makeApplyOnOriginalPrice(Promotion $promotion, array $items)
    {
        $newItems = [];

        if (!count($items)) {
            return [];
        }

        // Get Products from DB
        $products = $this->getProductsFromDB($items);
        Log::info('products before calculations');
        //Log::info($products);
        // Get All Products Price With gien Quantity
        $totalProductsPrice = $this->calculateTotalProductsPrice($items, $products);
        // Get Discount In Percentage from total products price * total products qty
        $discountPercentage = $this->getDiscountValueInPercentage($promotion, $totalProductsPrice);
        // $discountPercentage = round($discountPercentage, 2);    // 7.50 %


        // Apply Discount
        foreach ($items as $item) {
            $product = $products->where('id', $item['id'])->first();
            $totalPrice = $item['qty'] * $product->price;

            // Get Discounted for total quantities
            $totalDiscountedPrice = $this->calculateDiscountForProduct($totalPrice, $discountPercentage);
            // Get Discounted for the product price
            $discountedPrice = $this->calculateDiscountForProduct($product->price, $discountPercentage);

            $newItems[] = [
                'id'    => $product->id,
                'qty'   => $item['qty'],
                'total_discounted_price'    => $totalDiscountedPrice,
                'discounted_price'    => $discountedPrice,
                'margin'=> isset($item['margin'])?$item['margin']:0
            ];
        }

        return $newItems;
    }

    /**
     * Apply Promotion On Som Items
     */
    protected function makeApplyOnDiscountedPrice(Promotion $promotion, array $items)
    {
        $newItems = [];

        if (!count($items)) {
            return [];
        }

        // Get Products from DB
        $products = $this->getProductsFromDB($items);

        // Get All Products Price With gien Quantity
        $totalProductsPrice = $this->calculateTotalProductsPrice($items, $products, false);
        // Get Discount In Percentage from total products price * total products qty
        $discountPercentage = $this->getDiscountValueInPercentage($promotion, $totalProductsPrice);
        // $discountPercentage = round($discountPercentage, 2);    // 7.50 %

        // Apply Discount
        foreach ($items as $item) {
            $product = $products->where('id', $item['id'])->first();
            $price = $this->getProductPrice($product);
            $totalPrice = $item['qty'] * $price;

            // Get Discounted for total quantities
            $totalDiscountedPrice = $this->calculateDiscountForProduct($totalPrice, $discountPercentage);
            // Get Discounted for the product price
            $discountedPrice = $this->calculateDiscountForProduct($price, $discountPercentage);

            $newItems[] = [
                'id'    => $product->id,
                'qty'   => $item['qty'],
                'total_discounted_price'    => $totalDiscountedPrice,
                'discounted_price'    => $discountedPrice,
                'margin'=> isset($item['margin'])?$item['margin']:0
            ];
        }

        return $newItems;
    }

    /**
     * @param array $items
     * @return mixed
     */
    protected function calculateTotalProductsPrice(array $items, $productsFromDB, bool $original = true)
    {
        $totalPrice = 0;
        foreach ($items as $item) {

            if ($item['qty'] == 0) {
                continue;
            }
            $margin = isset($item['margin']) ? $item['margin'] : 0;
            $product = $productsFromDB->find($item['id']);
            $product->price = $product->price + ($product->price * $margin );
            $price = $product->price;
            Log::info($price);
            if (!$original) {
                $price = $this->getProductPrice($product);
            }
            Log::info($price);
            Log::info($item['qty']);
            Log::info($item);
            $totalPrice += $item['qty'] * $price;
            // $totalPrice += $item['qty'] * $product->price;
        }

        return $totalPrice;
    }

    /**
     * @param float $price
     * @param float $discountPercentage
     *
     * @return float
     */
    protected function calculateDiscountForProduct(float $price, float $discountPercentage)
    {
        return $price - (($discountPercentage / 100) * $price);
    }

    /**
     * @param Promotion $promotion
     * @param int $itemsCount
     * @return mixed
     */
    protected function getDiscountValueInPercentage(Promotion $promotion, float $totalPrice)
    {
        if ($promotion->discount_type == Promotion::DISCOUNT_TYPE_VALUE) {
            if ($totalPrice <= 0) {
                return 100;
            }
            return (($promotion->discount_value / $totalPrice) * 100);
        } else {
            return $promotion->discount_value;
        }
    }


    /**
     * Fetch Products from DB
     */
    protected function getProductsFromDB(array $items)
    {
        $itemsIDs = array_column($items, 'id');

        return Product::whereIn('id', $itemsIDs)->get();
    }

    /**
     * @param Product $product
     *
     * @return mixed
     */
    private function checkDiscountValidity(Product $product)
    {
        return collect($product->discount_details)
            ->where('start_validity', '<=', now()->format('Y-m-d H:i'))
            ->where('end_validity', '>=', now()->format('Y-m-d H:i'))
            ->where('area_id', request()->header('area'))
            ->first();
    }


    /**
     * @param Product $product
     *
     * @return mixed
     */
    private function getProductPrice(Product $product)
    {
        $price = $product->price;
        $selfDiscount = $this->checkDiscountValidity($product);
        if ($selfDiscount) {
            $price = $selfDiscount['discount_price'];
        }

        return $price;
    }

    /**
     * Reformate Products
     */
    protected function reformateItems($items, $newItems, $exceptItems)
    {
        // Get Products from DB
        $products = $this->getProductsFromDB($items);
        $formattedItems['discounted_items'] = [];
        $formattedItems['except_items'] = [];

        foreach ($newItems as $item) {
            $margin = isset($item['margin']) ? $item['margin'] : 0;
            $product = $products->where('id', $item['id'])->first();
            $product->price = $product->price + ($product->price * $margin );
            // Apply Promo Code
            $price = $item['qty'] * $product->price;

            $formattedItems['discounted_items'][] = [
                'id'    => $product->id,
                'qty'   => $item['qty'],
                'price'    => $product->price,
                'discounted_price'    => $item['discounted_price'],
                'total_price'    => $price,
                'total_discounted_price'    => $item['total_discounted_price'],
                'image'    => $product->image_url,
                'thumb'    => $product->thumb_url,
            ];
        }

        foreach ($exceptItems as $item) {
            $margin = isset($item['margin']) ? $item['margin'] : 0;
            $product = $products->where('id', $item['id'])->first();
            $product->price = $product->price + ($product->price * $margin);
            // Apply Promo Code
            $price = $item['qty'] * $product->price;

            $formattedItems['except_items'][] = [
                'id'    => $product->id,
                'qty'   => $item['qty'],
                'price'    => $product->price,
                'total_price'    => $price,
                'image'    => $product->image_url,
                'thumb'    => $product->thumb_url,
            ];
        }

        return $formattedItems;
    }
}
