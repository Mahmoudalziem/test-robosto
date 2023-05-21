<?php

use Illuminate\Support\Facades\Route;

Route::get('', 'Webkul\Core\Http\Controllers\HomeController@index');
Route::get('test-somthing', 'Webkul\Core\Http\Controllers\HomeController@testSomthing');

Route::group(['prefix' => 'api', 'middleware' => ['api']], function () {
        Route::get('static-text', 'Webkul\Core\Http\Controllers\CoreController@getText');
    }
);


Route::group(['prefix' => 'api/payment', 'middleware' => ['api']], function () {

    // Customer Auth Routes
    Route::group(['namespace' => 'Webkul\Core\Http\Controllers'], function () {

        Route::get('/success', [
            'as'    =>  'payment.success',
            'uses'  =>  'PaymentController@success'
        ]);

        Route::get('/fail', [
            'as'    =>  'payment.fail',
            'uses'  =>  'PaymentController@fail'
        ]);
    });
});
