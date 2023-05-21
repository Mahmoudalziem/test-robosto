<?php

namespace Webkul\Admin\Http\Controllers\ActivityLog;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Event;
use Illuminate\Pagination\LengthAwarePaginator;
use Webkul\Core\Http\Controllers\BackendBaseController;
use Webkul\Admin\Http\Resources\ActivityLog\ActivityLogAll;
use Webkul\Admin\Repositories\ActivityLog\ActivityLogRepository;
use Webkul\Admin\Http\Resources\ActivityLog\ActivityLogSingle as ActivityLogResource;
use Webkul\Core\Models\ActivityLog;

class ActivityLogController extends BackendBaseController
{
    /**
     * ActivityLogRepository object
     *
     * @var \Webkul\Admin\Repositories\ActivityLog\ActivityLogRepository
     */
    protected $activityLogRepository;


    /**
     * Create a new controller instance.
     *
     * @param  \Webkul\Admin\Repositories\ActivityLog\ActivityLogRepository  $activityLogRepository
     * @return void
     */
    public function __construct(ActivityLogRepository $activityLogRepository) {
        $this->activityLogRepository = $activityLogRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function list(Request $request)
    {
        $activityLogs = $this->activityLogRepository->list($request);

        $data['logs'] = new ActivityLogAll($activityLogs);
        $data['last_day'] = ActivityLog::first()->created_at->format('Y-m-d');
        if ($request->has('to_date') && !empty($request->to_date)) {
            $data['last_day'] = $request->to_date;
        }

        return $this->customResponsePaginatedSuccess($data, $request);
    }

    /**
     * @param $data
     * @param null $message
     * @param $request
     * @return JsonResponse
     */
    protected function customResponsePaginatedSuccess($data, $request, $message = null)
    {
        $response = null;
        if ($data['logs']->resource instanceof LengthAwarePaginator) {
            $response = $data['logs']->toResponse($request)->getData();
        }

        $response->last_day = $data['last_day'];
        $response->status = 200;
        $response->success = true;
        $response->message = $message;

        return response()->json($response);
    }

    /**
     * Show the specified activityLog.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function show(int $id)
    {
        $activityLog = $this->activityLogRepository->findOrFail($id);

        Event::dispatch('activityLog.show', $activityLog);

        return $this->responseSuccess(new ActivityLogResource($activityLog));

    }

}