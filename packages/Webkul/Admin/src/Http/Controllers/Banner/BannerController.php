<?php

namespace Webkul\Admin\Http\Controllers\Banner;

use Illuminate\Http\Request;
use App\Jobs\TriggerFCMRTDBJob;
use function DeepCopy\deep_copy;

use Webkul\Banner\Models\Banner;
use Illuminate\Support\Facades\Event;
use Webkul\Admin\Http\Resources\Banner\BannerAll;
use Webkul\Admin\Http\Resources\Banner\BannerSingle;
use Webkul\Admin\Repositories\Banner\BannerRepository;
use Webkul\Admin\Http\Requests\Banner\BannerAddRequest;
use Webkul\Core\Http\Controllers\BackendBaseController;


class BannerController extends BackendBaseController
{

    protected $bannerRepository;

    public function __construct(BannerRepository $bannerRepository) {
        $this->bannerRepository = $bannerRepository;
    }

    public function list($section,Request  $request)
    {
        $banners = $this->bannerRepository->list($section, $request);
        $data = new BannerAll($banners);
        return $this->responsePaginatedSuccess($data ,null, $request);
    }

    public function show(Banner $banner)
    {
           $banner=new BannerSingle($banner);
           return $this->responseSuccess( $banner  );
    }
    public function add(BannerAddRequest $request)
    {
        $banner = $this->bannerRepository->create($request->all());
        Event::dispatch('admin.banner.created', $banner);

        Event::dispatch('admin.log.activity', ['create', 'banner', $banner, auth('admin')->user(), $banner]);

        return $this->responseSuccess($banner);
    }

    public function update(Banner $banner,BannerAddRequest  $request)
    {
        $data = $request->all();
        $before = deep_copy($banner);

        $this->bannerRepository->update($data,$banner->id);

        TriggerFCMRTDBJob::dispatch('updated', 'banners');

        Event::dispatch('admin.log.activity', ['update', 'banner', $banner, auth('admin')->user(), $banner, $before]);

        return $this->responseSuccess( null,"Banner has been updated!" );
    }

    public function delete(Banner $banner)
    {
        $banner = $this->bannerRepository->findOrFail($banner->id);
        
        $banner->delete();

        TriggerFCMRTDBJob::dispatch('updated', 'banners');
        
        Event::dispatch('admin.log.activity', ['delete', 'banner', $banner, auth('admin')->user(), $banner]);

        return $this->responseSuccess( $banner ,'Banner Deleted!' );
    }

    public function setStatus(Banner $bannerModle,Request $request)
    {
        $this->validate($request, [
            'status'    =>  'required|numeric|in:0,1',
        ]);

        $before = deep_copy($bannerModle);
        $banner = $this->bannerRepository->setStatus($bannerModle,$request->only('status'));

        TriggerFCMRTDBJob::dispatch('updated', 'banners');
     
        Event::dispatch('admin.banner.set-status', $banner);
        Event::dispatch('admin.log.activity', ['update-status', 'banner', $banner, auth('admin')->user(), $banner, $before]);

        return $this->responseSuccess($banner);
    }

    public function setDefault(Banner $bannerModel,Request $request)
    {
        $this->validate($request, [
            'default'    =>  'required|numeric|in:0,1',
        ]);

        $before = deep_copy($bannerModel);
        $banner = $this->bannerRepository->setDefault( $bannerModel,$request->only('default'));

        TriggerFCMRTDBJob::dispatch('updated', 'banners');

        Event::dispatch('admin.banner.set-default', $banner);
        Event::dispatch('admin.log.activity', ['update', 'banner', $banner, auth('admin')->user(), $banner, $before]);
        
        return $this->responseSuccess($banner);
    }

    public function setPosition(Banner $bannerModel,Request $request)
    {
        $this->validate($request, [
            'position'    =>  'required|numeric',
        ]);

        $before = deep_copy($bannerModel);
        // new postion
        $oldPosition= $bannerModel->position;
        $newPosition= $request->position;

        $bannerToUpdate= $this->bannerRepository->findOneWhere(['area_id' => $bannerModel->area_id,'section'=>$bannerModel->section,'position'=>$newPosition]);

        // update other banner position
        if($bannerToUpdate){
            $bannerToUpdate->update(['position'=>$oldPosition]);
        }

        $banner = $this->bannerRepository->update(['position'=>$newPosition], $bannerModel->id);

        TriggerFCMRTDBJob::dispatch('updated', 'banners');

        Event::dispatch('admin.banner.set-position', $banner);
        Event::dispatch('admin.log.activity', ['update', 'banner', $banner, auth('admin')->user(), $banner, $before]);

        return $this->responseSuccess($banner);
    }

}
