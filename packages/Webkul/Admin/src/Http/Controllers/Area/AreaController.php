<?php

namespace Webkul\Admin\Http\Controllers\Area;

use Illuminate\Http\Request;
use Webkul\Core\Http\Controllers\BackendBaseController;
use App\Jobs\TriggerFCMRTDBJob;
use function DeepCopy\deep_copy;
use Illuminate\Support\Facades\Event;
use Webkul\Admin\Http\Requests\Area\AreaRequest;
use Webkul\Admin\Repositories\Area\AreaRepository;
use Webkul\Admin\Http\Resources\Area\AreaAll;
use Webkul\Admin\Http\Resources\Area\AreaSingle;
use Webkul\Area\Models\Area;


class AreaController extends BackendBaseController {

    protected $areaRepository;
    
    public function __construct(AreaRepository $areaRepository) {
        $this->areaRepository = $areaRepository;
    }

    public function list(Request  $request)
    {
        $areas = $this->areaRepository->list( $request);
        $data = new AreaAll($areas);
        return $this->responsePaginatedSuccess($data ,null, $request);
    }

    public function show(Area $area)
    {
           $area=new AreaSingle($area);
           return $this->responseSuccess( $area  );
    }
    public function add(AreaRequest $request)
    {
        $area = $this->areaRepository->create($request->all());
        Event::dispatch('admin.area.created', $area);

        Event::dispatch('admin.log.activity', ['create', 'area', $area, auth('admin')->user(), $area]);

        return $this->responseSuccess($area);
    }

    public function update(Area $area,AreaRequest  $request)
    {
        $data = $request->only('min_distance_between_orders');

        $before = deep_copy($area);

        $this->areaRepository->update($data,$area );

        TriggerFCMRTDBJob::dispatch('updated', 'areas');

        Event::dispatch('admin.log.activity', ['update', 'area', $area, auth('admin')->user(), $area, $before]);

        return $this->responseSuccess( null,"Area has been updated!" );
    }

    public function delete(Area $area)
    {
        $area = $this->areaRepository->findOrFail($area->id);
        
        $area->delete();

        TriggerFCMRTDBJob::dispatch('updated', 'areas');
        
        Event::dispatch('admin.log.activity', ['delete', 'area', $area, auth('admin')->user(), $area]);

        return $this->responseSuccess( $area ,'Area Deleted!' );
    }

    public function setStatus(Area $areaModle,Request $request)
    {
        $this->validate($request, [
            'status'    =>  'required|numeric|in:0,1',
        ]);

        $before = deep_copy($areaModle);
        $area = $this->areaRepository->setStatus($areaModle,$request->only('status'));

        TriggerFCMRTDBJob::dispatch('updated', 'areas');
     
        Event::dispatch('admin.area.set-status', $area);
        Event::dispatch('admin.log.activity', ['update-status', 'area', $area, auth('admin')->user(), $area, $before]);

        return $this->responseSuccess($area);
    }

    public function setDefault(Area $areaModel,Request $request)
    {
        $this->validate($request, [
            'default'    =>  'required|numeric|in:0,1',
        ]);

        $before = deep_copy($areaModel);
        $area = $this->areaRepository->setDefault( $areaModel,$request->only('default'));

        TriggerFCMRTDBJob::dispatch('updated', 'areas');

        Event::dispatch('admin.area.set-default', $area);
        Event::dispatch('admin.log.activity', ['update', 'area', $area, auth('admin')->user(), $area, $before]);
        
        return $this->responseSuccess($area);
    }    
}
