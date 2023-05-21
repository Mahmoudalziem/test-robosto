<?php

namespace Webkul\Core\Http\Controllers;

use Illuminate\Support\Facades\Event;
use Illuminate\View\View;
use Webkul\Core\Repositories\ChannelRepository;

class PaymentController extends BackendBaseController
{
    /**
     * Display a When Payment Success
     *
     * @return View
     */
    public function success()
    {
        return view('payment.success');
    }


    /**
     * Display a When Payment Fail
     *
     * @return View
     */
    public function fail()
    {
        return view('payment.fail');
    }

}