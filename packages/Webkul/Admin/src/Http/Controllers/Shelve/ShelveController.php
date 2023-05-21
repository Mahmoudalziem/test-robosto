<?php

namespace Webkul\Admin\Http\Controllers\Shelve;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Webkul\Admin\Http\Resources\Shelve\ShelveAll;
use Webkul\Admin\Http\Resources\Shelve\ShelveSingle as ShelveResource;
use Webkul\Core\Http\Controllers\BackendBaseController;
use Webkul\Admin\Http\Requests\Shelve\ShelveRequest;
use Webkul\Admin\Repositories\Shelve\ShelveRepository;
use function DeepCopy\deep_copy;

class ShelveController extends BackendBaseController
{

    /**
     * ShelveRepository object
     *
     * @var \Webkul\Admin\Repositories\Shelve\ShelveRepository
     */
    protected $shelveRepository;


    /**
     * Create a new controller instance.
     *
     * @param  \Webkul\Admin\Repositories\Shelve\ShelveRepository  $shelveRepository
     * @return void
     */
    public function __construct(ShelveRepository $shelveRepository) {
        $this->shelveRepository = $shelveRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function list(Request $request)
    {
        $shelves = $this->shelveRepository->list($request);

        $data = new ShelveAll($shelves);

        return $this->responsePaginatedSuccess($data ,null, $request);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create(ShelveRequest $request)
    {
        $shelve = $this->shelveRepository->create($request->all());

        Event::dispatch('shelve.created', $shelve);
        Event::dispatch('admin.log.activity', ['create', 'shelve', $shelve, auth('admin')->user(), $shelve]);

        return $this->responseSuccess($shelve);
    }

    /**
     * Show the specified shelve.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function show(int $id)
    {
        $shelve = $this->shelveRepository->findOrFail($id);

        Event::dispatch('shelve.show', $shelve);

        return $this->responseSuccess(new ShelveResource($shelve));

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function update(ShelveRequest $request, $id)
    {
        $shelve = $this->shelveRepository->findOrFail($id);
        $before = deep_copy($shelve);
        
        $shelve = $this->shelveRepository->update($request->all(), $shelve);
        
        Event::dispatch('shelve.updated', $shelve);
        Event::dispatch('admin.log.activity', ['update', 'shelve', $shelve, auth('admin')->user(), $shelve, $before]);

        return $this->responseSuccess($shelve);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function delete($id)
    {
        $shelve = $this->shelveRepository->findOrFail($id);
        
        $this->shelveRepository->delete($id);

        Event::dispatch('admin.log.activity', ['delete', 'shelve', $shelve, auth('admin')->user(), $shelve]);

        return $this->responseSuccess(null);
    }

}