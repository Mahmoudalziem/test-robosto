<?php



    Route::group(['prefix' => 'api'], function () {
        Route::group(['namespace' => 'Webkul\Inventory\Http\Controllers\Api', 'middleware' => ['locale', 'theme', 'currency']], function ($router) {
            Route::get('inventory-sources/', 'InventorySourceController@getAll');
            Route::get('inventory-sources/{id}', 'InventorySourceController@get');
            Route::post('inventory-sources/create', 'InventorySourceController@create');
            Route::put('inventory-sources/create', 'InventorySourceController@create');
        });

    });



