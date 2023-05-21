<?php

namespace Webkul\User\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;


class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    // protected $listen = [
    //     'App\Events\Event' => [
    //         'App\Listeners\EventListener',
    //     ],
    // ];

    /**
     * Register any events for your application.
     *
     * @return void
     *
     */
    public function boot()
    {
        Event::listen('user.forgetpasswordSend-OTPEmail.after', "Webkul\User\Listeners\ForgetPasswordOTPMail@handle" );
    }
}