<?php

namespace Webkul\Admin\Http\Controllers\Core;

use Illuminate\Support\Facades\Request;

use Webkul\Core\Http\Controllers\BackendBaseController;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\Area\Repositories\AreaRepository;
use Webkul\Area\Models\Area;
class CoreController extends BackendBaseController {

    protected $areaRepository;
    protected $channelRepository;

    public function __construct(
            AreaRepository $areaRepository,
            ChannelRepository $channelRepository
    ) {
        $this->areaRepository = $areaRepository;
        $this->channelRepository = $channelRepository;
    }

    public function areaList( ) {
        $request=request();
        $query = isset($request['text']) ? $request['text'] : '';
        $query = Area::byArea()->whereTranslationLike('name', '%' . $query . '%')->active();
        $data = $query->get( );
 
        return $this->responseSuccess($data);
    }

    public function channelList() {
        return $this->responseSuccess($this->channelRepository->all());
    }

}
