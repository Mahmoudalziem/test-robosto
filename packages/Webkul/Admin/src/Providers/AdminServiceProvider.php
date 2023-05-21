<?php

namespace Webkul\Admin\Providers;

use Illuminate\Support\ServiceProvider;

class AdminServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__ . '/../Http/routes.php');
        $this->publishes([
            dirname(__DIR__) . '/Config/permissions.php' => config_path('permissions.php'),
        ]);
        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'admin');

        $this->app->register(EventServiceProvider::class);
    }

 
    
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerConfig();
    }

    /**
     * Register package config.
     *
     * @return void
     */
    protected function registerConfig()
    {
   

        $this->mergeConfigFrom(
            dirname(__DIR__) . '/Config/permissions.php', 'permissions'
        );
    }    
}
