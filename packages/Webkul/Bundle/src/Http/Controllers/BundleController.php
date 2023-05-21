<?php

namespace Webkul\Bundle\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;
use Webkul\Bundle\Http\Resources\Bundle as BundleResource;
use Webkul\Bundle\Http\Resources\BundleAll;
use Webkul\Bundle\Repositories\BundleRepository;
use Webkul\Category\Repositories\SubCategoryRepository;
use Webkul\Core\Http\Controllers\BackendBaseController;

class BundleController extends BackendBaseController
{
    /**
     * BundleRepository object
     *
     * @var BundleRepository
     */
    protected $bundleRepository;

    /**
     * Create a new controller instance.
     *
     * @param BundleRepository $bundleRepository
     * @return void
     */
    public function __construct(BundleRepository $bundleRepository)
    {
        $this->bundleRepository = $bundleRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $bundles = $this->bundleRepository->all();

        Event::dispatch('app-bundles.fetched', $bundles);

        return $this->responseSuccess($bundles);
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function popular(Request $request)
    {
        $bundles = $this->bundleRepository->active()->hasAmount()->limit(6)->get();

        Event::dispatch('app-bundles.popular', $bundles);

        $data = new BundleAll($bundles);

        return $this->responseSuccess($data);
    }


    /**
     * Search for Bundles.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request)
    {
        $bundles = $this->bundleRepository->search($request);

        Event::dispatch('app-bundles.popular', $bundles);

        $data = new BundleAll($bundles);

        return $this->responsePaginatedSuccess($data, null, $request);
    }

    /**
     * Get bundles by sub category.
     *
     * *
     * @param Request $request
     * @param SubCategoryRepository $subCategoryRepository
     * @param int $id
     * @return JsonResponse
     */
    public function getBundlesBySubCategory(Request $request, SubCategoryRepository $subCategoryRepository, $id)
    {
        // Find SubCategory
        $subCategory = $subCategoryRepository->findOrFail($id);

        $bundles = $this->bundleRepository->bundlesBySubCategory($request, $subCategory);

        $data = new BundleAll($bundles);

        return $this->responsePaginatedSuccess($data, null, $request);
    }


    /**
     * Show the specified bundle.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function show($id)
    {
        $bundle = $this->bundleRepository->with('subCategories')->findOrFail($id);

        if (auth('customer')->check()) {
            Event::dispatch('app-bundles.show', [$bundle, auth('customer')->user()]);
        }


        $data = new BundleResource($bundle);

        return $this->responseSuccess($data);
    }

    /** Calculate Payment Summary
     * @param Request $request
     * @param BundleRepository $bundleRepository
     * @return JsonResponse
     */
    public function paymentSummary(Request $request, BundleRepository $bundleRepository)
    {
        $summary = $bundleRepository->paymentSummary($request->only(['items', 'promo_code']));

        return $this->responseSuccess($summary);
    }
}
