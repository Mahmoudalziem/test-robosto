<?php

namespace Webkul\Discount\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;

class DiscountServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

        $this->loadRoutesFrom(__DIR__ . '/../Http/routes.php');
 

        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'discount');

        $this->publishes([
            __DIR__ . '/../../publishable/assets' => public_path('themes/default/assets'),
        ], 'public');
 
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