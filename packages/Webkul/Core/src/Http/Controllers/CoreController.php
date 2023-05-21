<?php

namespace Webkul\Core\Http\Controllers;

use Illuminate\View\View;
use Illuminate\Http\Request;
use Webkul\Driver\Models\Driver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Webkul\Sales\Models\OrderDriverDispatch;
use Webkul\Core\Repositories\ChannelRepository;

class CoreController extends BackendBaseController
{
    /**
     * @return JsonResponse
     */
    public function getText(Request $request)
    {
        $data = [
            'share_text'    =>  __('core::app.share_text'),
            'promo_text'    =>  __('core::app.promo_text'),
            'delivery_time' =>  config('robosto.DELIVERY_TIME'),
        ];
        return $this->responseSuccess($data);
    }
   
}
