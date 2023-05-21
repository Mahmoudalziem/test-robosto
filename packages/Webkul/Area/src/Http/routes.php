<?php

Route::group(['prefix' => 'api/area', 'middleware' => ['api']], function () {
    Route::group(['namespace' => 'Webkul\Area\Http\Controllers\Api'], function ($router) {
        Route::get('/', 'AreaController@get');
        Route::get('/addresses', 'AreaController@areaAddresses');
        Route::post('/create', 'AreaController@create');
    });

});


