<?php

namespace Webkul\Core\Providers;

use Webkul\Core\Core;
use Illuminate\Contracts\Container\BindingResolutionException;
use App\Exceptions\Handler;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;
use Webkul\Core\Facades\Core as CoreFacade;
use Illuminate\Contracts\Debug\ExceptionHandler;

class CoreServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        include __DIR__ . '/../Http/helpers.php';

        $this->loadRoutesFrom(__DIR__ . '/../Http/routes.php');

        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'core');

        $this->publishes([
            dirname(__DIR__) . '/Config/concord.php' => config_path('concord.php'),
            dirname(__DIR__) . '/Config/scout.php' => config_path('scout.php'),
        ]);

        $this->app->bind(
            ExceptionHandler::class,
            Handler::class
        );
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'core');
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerFacades();
    }

    /**
     * Register Core as a singleton.
     *
     * @return void
     */
    protected function registerFacades()
    {
        $loader = AliasLoader::getInstance();
        $loader->alias('core', CoreFacade::class);

        $this->app->singleton('core', function () {
            return app()->make(Core::class);
        });
    }

}
