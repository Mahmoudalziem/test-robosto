<?php

namespace Webkul\User\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;

class UserServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(Router $router)
    {
        $this->app->register(EventServiceProvider::class);

        include __DIR__ . '/../Http/helpers.php';

        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');


        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'user');
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
    }
}
