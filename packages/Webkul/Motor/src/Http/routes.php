<?php
use Illuminate\Http\Request;
use Illuminate\Routing\Router;

Route::group(['prefix' => 'api'], function () {

    Route::group(['namespace' => 'Webkul\Motor\Http\Controllers\Api', 'middleware' => ['api' ,'auth:admin']], function ($router) {
        Route::post('motors', 'MotorController@create');
    });
    Route::group(['namespace' => 'Webkul\Motor\Http\Controllers\Api', 'middleware' => [ 'api','auth:driver' ]], function ($router) {
        Route::get('motors/{id}', 'MotorController@getById');
        Route::get('motors', 'MotorController@get'); // get all
    });

});

