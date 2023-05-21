<?php

namespace Webkul\Category\Http\Controllers;

use Illuminate\Http\Request;
use Webkul\Product\Models\Product;
use Illuminate\Support\Facades\Event;
use Webkul\Inventory\Models\InventoryArea;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Category\Repositories\SubCategoryRepository;
use Webkul\Core\Http\Controllers\BackendBaseController;
use Webkul\Admin\Http\Resources\Category\SubCategoryAll;
use Webkul\Category\Http\Resources\SubCategory as SubCategoryResource;
use Webkul\Category\Http\Resources\PopularSubCategories;

class SubCategoryController extends BackendBaseController
{

    /**
     * SubCategoryRepository object
     *
     * @var \Webkul\Category\Repositories\SubCategoryRepository
     */
    protected $subCategoryRepository;

    /**
     * Create a new controller instance.
     *
     * @param  \Webkul\Category\Repositories\SubCategoryRepository  $subCategoryRepository
     * @return void
     */
    public function __construct(SubCategoryRepository $subCategoryRepository)
    {
        $this->subCategoryRepository = $subCategoryRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $subCategories = $this->subCategoryRepository->positioned()->all();

        return $this->responseSuccess($subCategories);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function popular()
    {
        $categories = $this->subCategoryRepository->active()->positioned()->get();

        $data = new PopularSubCategories($categories);

        return $this->responseSuccess($data);
    }

    public function byAreaId( )
    {
        $areaId = request()->header('areaId') ;
        $subCategories = $this->subCategoryRepository->active()->positioned()->with('products');
        $subCategories=$subCategories->whereHas('products',function($query) use($areaId) {
             $query->whereHas('areas',function($q) use($areaId,$query){
                 $q->where('area_id',$areaId)
                   ->where('total_qty','>',0);
             });
        });
        $data=new SubCategoryAll($subCategories->all());
        return $this->responseSuccess($data);
    }

    public function ProductsbySubcategoryId($id )
    {
        $areaId = request()->header('areaId') ;
        $prod=  Product::query() ;
        // get all products that has stock on selected area
        $products=$prod->whereHas('areas',function($query) use($areaId) {
            $query->where('area_id',$areaId)
                    ->where('total_qty','>',0);
        });
        // filter products that has been selected before by sub category id
        $products=$products->whereHas('subCategories',function($query) use($id) {
           $query->where('sub_category_id',$id);
        });
        return $this->responseSuccess($products->get());
    }

    /**
     * Show the specified category.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $category = $this->subCategoryRepository->with('parentCategories')->findOrFail($id);

        $data = new SubCategoryResource($category);

        return $this->responseSuccess($data);
    }

    /**
     * Get products by sub category.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function getProducts($id)
    {
        // Find SubCategory
        $subCategory = $this->subCategoryRepository->active()->positioned()->with('products')->findOrFail($id);

        // Get Products for this Sub Category
        $products = $subCategory->products;

        return $this->responseSuccess($products);
    }

}