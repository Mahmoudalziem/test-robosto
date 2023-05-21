<?php

namespace Webkul\Core\Http\Controllers;

use App\Jobs\TestJob;
use Illuminate\View\View;
use Webkul\Core\Models\Tag;
use Illuminate\Http\Request;
use Webkul\Sales\Models\Order;
use Webkul\Driver\Models\Driver;
use Webkul\Core\Services\Measure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Webkul\Customer\Models\Customer;
use Illuminate\Support\Facades\Event;
use Webkul\Sales\Models\OrderAddress;
use Webkul\Core\Services\FixSKUs\FixSkus;
use Webkul\Sales\Models\OrderDriverDispatch;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\Driver\Repositories\DriverRepository;
use Webkul\Driver\Services\CalculateWorkingBonus;
use Webkul\Customer\Http\Controllers\Auth\SMSTrait;
use Webkul\Sales\Repositories\Traits\OrderNotifications;
use Webkul\Sales\Repositories\Traits\CollectorRoundRobin;
use Webkul\Core\Services\RetentionMessages\RetentionMessages;
use Webkul\Sales\Repositories\OrderServices\RoboDistanceService;

class HomeController extends BackendBaseController
{
    use SMSTrait, CollectorRoundRobin, OrderNotifications;
    /**
     * Display a listing of the resource.
     *
     * @return View
     */
    public function index()
    {
        // return view('core::payment.success');
        return view('welcome');
    }


    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function testSomthing(Request $request, DriverRepository $driverRepository)
    {

        Log::info("Home Controller");
        
        // (new FixSkus)->startFix();
        
        // for ($i=1; $i <= 10; $i++) { 
        //     TestJob::dispatch($i);
        //     Event::dispatch('test.list', $i);
        // }
        // Event::dispatch('driver.order-cancelled', 352);
        // Event::dispatch('driver.order-delivered-bonus', 45);
        // Event::dispatch('driver.new-order-assigned', [352]);
        // Event::dispatch('driver.start-delivery', [1]);
        // Event::dispatch('driver.order-delivered', [166]);
        
        // (new RoboDistanceService())->collectorPreparedOrder(Order::find(354));
        
        // dd(Measure::distanceOne(29.965745, 31.270332, 29.971474, 31.285042, 'K', null));
        
        return response()->json(["status" => "OK"], 200);
    }
}
