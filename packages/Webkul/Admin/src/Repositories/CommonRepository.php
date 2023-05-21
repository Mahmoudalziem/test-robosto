<?php

namespace Webkul\Admin\Repositories;

use Webkul\Brand\Models\Brand;
use Webkul\Core\Models\Shelve;
use Webkul\Product\Models\Unit;
use Webkul\Product\Models\Product;
use Webkul\Category\Models\Category;
use Webkul\Core\Eloquent\Repository;
use Webkul\Supplier\Models\Supplier;
use Webkul\Product\Models\ProductTag;
use Webkul\Product\Models\Productlabel;
use Webkul\Core\Models\Tag;
use Illuminate\Support\Facades\Event;
use Webkul\Area\Models\Area;
use Webkul\Inventory\Models\Warehouse;
use Illuminate\Support\Facades\Storage;
use Webkul\Category\Models\SubCategory;
use Webkul\Sales\Models\PaymentMethod;

class CommonRepository extends Repository
{
    /**
     * @param $type
     * @param $request
     * @return mixed
     */
    public function list($type, $request) {

        $query = isset($request['text']) ? $request['text'] : '';

        if ($type == 'categories') {
            $query = Category::whereTranslationLike('name', '%'. $query .'%')->active();
        } elseif ($type == 'subCategory') {
            $query = SubCategory::whereTranslationLike('name', '%'. $query .'%')->active();
        } elseif ($type == 'units') {
            $query = Unit::whereTranslationLike('name', '%'. $query .'%')->active();
        } elseif ($type == 'areas') {
            $query = Area::byArea()->whereTranslationLike('name', '%'. $query .'%')->active();
        } elseif ($type == 'warehouses') {
            $query = Warehouse::byArea()->whereTranslationLike('name', '%'. $query .'%');
        } elseif ($type == 'suppliers') {
            $query = Supplier::where('name', 'LIKE', '%'. $query .'%')->active();
        } elseif ($type == 'brands') {
            $query = Brand::whereTranslationLike('name', '%'. $query .'%')->active();
        } elseif ($type == 'shelves') {
            $query = Shelve::where('name', 'LIKE', '%'. $query .'%')->orderBy('position');
        } elseif ($type == 'paymentMethods') {
            $query = PaymentMethod::active();
        } elseif ($type == 'productTags') {
            $query = ProductTag::whereTranslationLike('name', '%'. $query .'%');  
        } elseif ($type == 'productLabels') {
            $query = Productlabel::whereTranslationLike('name', '%'. $query .'%')->active();             
        } else {
            $query = $this->model->whereTranslationLike('name', '%'. $query .'%')->active()->limit(20);
        }

        $perPage = $request->has('per_page') ? (int) $request->per_page : 20;
        $pagination = $query->paginate($perPage);
        $pagination->appends([
            'sort' => $request->sort,
            'filter' => $request->filter,
            'per_page' => $request->per_page
        ]);

        return $pagination;
    }
    
    public function getAll($type, $request) {

        $query = isset($request['text']) ? $request['text'] : '';

        if ($type == 'categories') {
            $query = Category::whereTranslationLike('name', '%'. $query .'%')->active();
        } elseif ($type == 'subCategory') {
            $query = SubCategory::whereTranslationLike('name', '%'. $query .'%')->active();
        } elseif ($type == 'units') {
            $query = Unit::whereTranslationLike('name', '%'. $query .'%')->active();
        } elseif ($type == 'areas') {
            $query = Area::byArea()->whereTranslationLike('name', '%'. $query .'%')->active();
        } elseif ($type == 'warehouses') {
            $query = Warehouse::byArea()->whereTranslationLike('name', '%'. $query .'%')->active();            
        } elseif ($type == 'suppliers') {
            $query = Supplier::where('name', 'LIKE', '%'. $query .'%')->active();
        } elseif ($type == 'brands') {
            $query = Brand::whereTranslationLike('name', '%'. $query .'%')->active();
        } elseif ($type == 'shelves') {
            $query = Shelve::where('name', 'LIKE', '%'. $query .'%')->orderBy('position');
        } elseif ($type == 'paymentMethods') {
            $query = PaymentMethod::active();
        } elseif ($type == 'productTags') {
            $query = ProductTag::whereTranslationLike('name', '%'. $query .'%');  
        } elseif ($type == 'productLabels') {
            $query = Productlabel::whereTranslationLike('name', '%'. $query .'%')->active();   
        } elseif ($type == 'tags') {
            $query = Tag::where('name', 'LIKE', '%'. $query .'%') ;            
        } else {
            $query = $this->model->whereTranslationLike('name', '%'. $query .'%')->active()->limit(20);
        }

 
        $all = $query->get( );
  

        return $all;
    }    
    /**
     * @return string
     */
    public function model()
    {
        return Product::class;
    }
}
