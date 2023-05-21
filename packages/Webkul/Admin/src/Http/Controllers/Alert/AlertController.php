<?php

namespace Webkul\Admin\Http\Controllers\Alert;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Webkul\Core\Http\Controllers\BackendBaseController;
use Webkul\Admin\Repositories\Alert\AlertRepository;
use Webkul\Admin\Http\Resources\Alert\AlertAll;
use Illuminate\Pagination\LengthAwarePaginator;

class AlertController extends BackendBaseController {

    protected $alertRepository;

    public function __construct(AlertRepository $alertRepository) {
        $this->alertRepository = $alertRepository;
    }

    public function index(Request $request) {
        $alerts = $this->alertRepository->list($request);
        $data = new AlertAll($alerts);
        return $this->responsePaginatedSuccess($data, null, $request);
    }

    public function me(Request $request) {
        $alerts = $this->alertRepository->me(auth('admin')->user(), $request);
        $data['alerts'] = new AlertAll($alerts);
        $data['unread_count'] = $this->alertRepository->unreadCount(auth('admin')->user());
        return $this->customResponsePaginatedSuccess($data, null, $request);
    }

    public function read(Request $request) {
        $this->alertRepository->read(auth('admin')->user(), $request);
        return $this->responseSuccess();
    }

    /**
     * @param $data
     * @param null $message
     * @param $request
     * @return JsonResponse
     */
    protected function customResponsePaginatedSuccess($data, $message = null, $request) {

        $response = null;

        if ($data['alerts']->resource instanceof LengthAwarePaginator) {
            $response = $data['alerts']->toResponse($request)->getData();
        }

        $response->unread_count = $data['unread_count'];
        $response->status = 200;
        $response->success = true;
        $response->message = $message;

        return response()->json($response);
    }

}
