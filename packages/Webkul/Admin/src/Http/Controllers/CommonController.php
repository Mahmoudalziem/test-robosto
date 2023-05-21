<?php

namespace Webkul\Admin\Http\Controllers;

use Illuminate\Http\Request;
use Webkul\Area\Models\Area;
use Illuminate\Http\JsonResponse;
use Webkul\Admin\Http\Resources\FetchAll;
use Webkul\Admin\Repositories\CommonRepository;
use Webkul\Core\Http\Controllers\BackendBaseController;


class CommonController extends BackendBaseController
{

    /**
     * ProductRepository object
     *
     * @var CommonRepository
     */
    protected $commonRepository;

    /**
     * Create a new controller instance.
     *
     * @param CommonRepository $commonRepository
     */
    public function __construct(CommonRepository $commonRepository)
    {
        $this->commonRepository = $commonRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @param $type
     * @return JsonResponse
     */
    public function fetchAll(Request $request, $type)
    {
        $response = $this->commonRepository->list($type, $request);
        $data = new FetchAll($response);
        return $this->responsePaginatedSuccess($data, null, $request);
    }
    
    
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @param $type
     * @return JsonResponse
     */
    public function hashTags(Request $request)
    {
        $hashTags = config('robosto.HASHTAGS');

        return $this->responseSuccess($hashTags, null, $request);
    }


    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @param $type
     * @return JsonResponse
     */
    public function getAreasWithWarehouses(Request $request)
    {
        // Response Format
        $response = [
            'id'    =>  'all',
            'label' =>  'All',
            'children'  =>  []
        ];

        // Get Areas with it's warehouses
        $areas = Area::with(['warehouses'])->get();

        foreach ($areas as $area) {
            $warehouses = [];
            if (count($area->warehouses)) {
                foreach ($area->warehouses as $warehouse) {
                    $warehouses[] = [
                        'id'        =>  $warehouse->id,
                        'label'    =>  $warehouse->name,
                    ];
                }

                $response['children'][] = [
                    'id'    =>  'area-' . $area->id,
                    'label' =>  $area->name,
                    'children'  =>  $warehouses
                ];
            }
        }

        return $this->responseSuccess($response);
    }

    // without pagination
    public function getAll(Request $request, $type)
    {
        $response = $this->commonRepository->getAll($type, $request);
        $data = new FetchAll($response);
        return $this->responseSuccess($data);
    }
}
