<?php

namespace Webkul\Motor\Providers;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;

/**
* HelloWorld service provider
*
* @author    Jane Doe <janedoe@gmail.com>
* @copyright 2018 Webkul Software Pvt Ltd (http://www.webkul.com)
*/
class MotorServiceProvider extends ServiceProvider
{
/**
* Bootstrap services.
*
* @return void
*/
public function boot(Router $router)
{
    $this->loadRoutesFrom(__DIR__ . '/../Http/routes.php');
    $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'driver');
    $this->loadMigrationsFrom(__DIR__ .'/../Database/Migrations');
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