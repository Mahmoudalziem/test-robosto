<?php

namespace Webkul\Brand\Http\Controllers;

use Illuminate\Support\Facades\Event;
use Webkul\Brand\Repositories\BrandRepository;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Core\Http\Controllers\BackendBaseController;

class BrandController extends BackendBaseController
{
    
    /**
     * BrandRepository object
     *
     * @var \Webkul\Brand\Repositories\BrandRepository
     */
    protected $brandRepository;

    /**
     * Create a new controller instance.
     *
     * @param  \Webkul\Brand\Repositories\BrandRepository  $brandRepository
     * @return void
     */
    public function __construct(BrandRepository $brandRepository)
    {
        $this->brandRepository = $brandRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // app()->setLocale('ar');
        $brands = $this->brandRepository->all();

        return $this->responseSuccess($brands, 'Brands Returned Sucessfully');
    }


    /**
     * Show the specified brand.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $brand = $this->brandRepository->with('subBrands')->findOrFail($id);

        return $this->responseSuccess($brand, 'Brand Returned Sucessfully');
    }
}
