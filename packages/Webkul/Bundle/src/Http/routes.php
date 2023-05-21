<?php

Route::group(['prefix' => 'api/bundles', 'middleware' => ['api']], function () {

    // Customer Auth Routes
    Route::group(['namespace' => 'Webkul\Bundle\Http\Controllers'], function () {

        Route::get('/', [
            'as'    =>  'bundles.index',
            'uses'  =>  'BundleController@index'
        ]);
    });
});
