<?php

namespace Webkul\Product\Listeners;

use Webkul\Product\Models\Product;
use Webkul\Product\Repositories\ProductRepository;


class ProductAction
{

    protected $productRepository;


    public function __construct(ProductRepository  $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    public function updateVisitsCount(Product $product, $customer)
    {
        $product->visits_count=$product->visits_count+1;
        $product->save();
        $product->customers()->attach($customer->id);
    }

}
