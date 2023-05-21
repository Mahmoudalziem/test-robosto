<?php

namespace Webkul\Area\Providers;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;

/**
* HelloWorld service provider
*
* @author    Jane Doe <janedoe@gmail.com>
* @copyright 2018 Webkul Software Pvt Ltd (http://www.webkul.com)
*/
class AreaServiceProvider extends ServiceProvider
{
/**
* Bootstrap services.
*
* @return void
*/
public function boot()
{
    //  include __DIR__ . '/../Http/routes.php';
    $this->loadRoutesFrom(__DIR__ . '/../Http/routes.php');
    $this->loadViewsFrom(__DIR__ . '/../Resources/views/', 'area');
    $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'area');
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