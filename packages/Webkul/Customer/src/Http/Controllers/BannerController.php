<?php

namespace Webkul\Customer\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Webkul\Banner\Repositories\BannerRepository;
use Webkul\Core\Http\Controllers\BackendBaseController;
use Webkul\Customer\Http\Resources\Banner\BannerAll;

class BannerController extends BackendBaseController
{

    protected $bannerRepository;
    public function __construct(
        BannerRepository $bannerRepository
    )
    {
        $this->bannerRepository = $bannerRepository;
    }
    public function list($section,Request $request)
    {

        $banners = $this->bannerRepository->list($section,$request);
        $data= new BannerAll($banners);
        // Fire Event
        Event::dispatch('customer.banner.list', $banners);

        return $this->responseSuccess(  $data);
    }

}
