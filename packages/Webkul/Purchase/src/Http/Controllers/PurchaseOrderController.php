<?php

namespace Webkul\Purchase\Http\Controllers;

use Illuminate\Support\Facades\Event;
use Webkul\Purchase\Repositories\PurchaseOrderRepository;
use Webkul\Category\Repositories\SubCategoryRepository;
use Webkul\Core\Http\Controllers\BackendBaseController;

class PurchaseOrderController extends BackendBaseController
{
    /**
     * PurchaseOrderRepository object
     *
     * @var \Webkul\Purchase\Repositories\PurchaseOrderRepository
     */
    protected $productRepository;

    /**
     * Create a new controller instance.
     *
     * @param  \Webkul\Purchase\Repositories\PurchaseOrderRepository  $productRepository
     * @return void
     */
    public function __construct(PurchaseOrderRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return $this->responseSuccess();
    }

    /**
     * Get products by sub category.
     *
     * * @param  \Webkul\Category\Repositories\SubCategoryRepository  $subCategoryRepository
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function getPurchasesBySubCategory(SubCategoryRepository $subCategoryRepository, $id)
    {
        // Find SubCategory
        $subCategory = $subCategoryRepository->with('products')->findOrFail($id);
        
        // Get Purchases for this Sub Category
        $products = $subCategory->products;

        return $this->responseSuccess($products);
    }


    /**
     * Show the specified product.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $product = $this->productRepository->with('subCategories')->findOrFail($id);

        Event::dispatch('app-products.show', $product);

        return $this->responseSuccess($product);
    }

}