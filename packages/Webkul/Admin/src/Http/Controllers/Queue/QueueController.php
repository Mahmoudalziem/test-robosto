<?php

namespace Webkul\Admin\Http\Controllers\Queue;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Webkul\Core\Http\Controllers\BackendBaseController;
use Webkul\Customer\Http\Controllers\Auth\SMSTrait;
use Webkul\Admin\Repositories\SmsCampaign\SmsCampaignRepository;
use Webkul\Admin\Http\Requests\SmsCampaign\SmsCampaignRequest;
use Webkul\Admin\Http\Resources\SmsCampaign\SmsCampaignAll;
use App\Jobs\PublishSmsCampaign;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class QueueController extends BackendBaseController {

    public function __construct() {
        
    }

    public function generate() {

        $driver = \Webkul\Driver\Models\Driver::get();
        foreach ($driver as $driver) {
            for ($i = 0; $i <= 2; $i++) {
                \App\Jobs\DriverBreakToIdle::dispatch($driver)
                        ;
                ;
            }
        }
//       for($i=0;$i<=100000;$i++){
//           \App\Jobs\DriverBreakToIdle::dispatch($driver)  ;
//       }

        return $this->responseSuccess('New Driver has been updated!');
    }

}
