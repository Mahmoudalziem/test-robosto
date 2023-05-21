<?php

Route::group(['prefix' => 'api/promotions', 'middleware' => ['api']], function () {

    // Customer Auth Routes
    Route::group(['namespace' => 'Webkul\Promotion\Http\Controllers'], function () {

        Route::get('/', [
            'as'    =>  'promotions.index',
            'uses'  =>  'PromotionController@index'
        ]);

        Route::post('check-promotion', [
            'as'    =>  'promotions.check-promotion',
            'uses'  =>  'PromotionController@checkPromotion'
        ])->middleware('auth:customer');

    });

});




