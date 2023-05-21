<?php

Route::group(['prefix' => 'api/products', 'middleware' => ['api']], function () {

    // Customer Auth Routes
    Route::group(['namespace' => 'Webkul\Product\Http\Controllers', 'middleware' => ['shadow.area']], function () {

        Route::get('/', [
            'as'    =>  'products.index',
            'uses'  =>  'ProductController@index'
        ]);

        Route::get('/popular', [
            'as'    =>  'products.popular',
            'uses'  =>  'ProductController@popular'
        ]);
        
        Route::get('/new-arrivals', [
            'as'    =>  'products.new-arrivals',
            'uses'  =>  'ProductController@newArrivals'
        ]);

        Route::get('search', [
            'as'    =>  'products.search',
            'uses'  =>  'ProductController@search'
        ]);

        Route::get('sub-category/{id}', [
            'as'    =>  'products.sub_category',
            'uses'  =>  'ProductController@getProductsBySubCategory'
        ]);

        Route::get('show/{id}', [
            'as'    =>  'products.show',
            'uses'  =>  'ProductController@show'
        ]);

        Route::post('/payment-summary', [
            'as'    =>  'payment.summary',
            'uses'  =>  'ProductController@paymentSummary'
        ]);

    });

});




