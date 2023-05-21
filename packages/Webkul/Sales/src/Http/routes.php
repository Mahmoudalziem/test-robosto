<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'api/orders', 'middleware' => ['api']], function () {

    Route::get('test-notification', [
        'uses' => 'Webkul\Sales\Http\Controllers\OrderController@testNotification'
    ]);
    
    Route::get('initiate-call', [
        'uses' => 'Webkul\Sales\Http\Controllers\OrderController@initiateCall'
    ]);
    
    Route::post('call-callback', [
        'uses' => 'Webkul\Sales\Http\Controllers\OrderController@callCallback'
    ]);

    Route::post('order-confirmation-callback', [
        'uses' => 'Webkul\Sales\Http\Controllers\OrderController@orderConfirmationCallback'
    ]);

    // Customer Auth Routes
    Route::group([
        'namespace' => 'Webkul\Sales\Http\Controllers',
        'middleware' => [
            'auth:customer',
            'customer.must.be.active',
        ]
    ], function () {

        Route::get('/payments', [
            'as' => 'payments',
            'uses' => 'OrderController@getPayments'
        ]);

        Route::post('/create', [
            'as' => 'orders.create',
            'uses' => 'OrderController@create'
        ]);

        Route::post('/create/schedule', [
            'as' => 'orders.create.schedule',
            'uses' => 'OrderController@createSchedule'
        ]);

        Route::get('show/{id}', [
            'as' => 'orders.show',
            'uses' => 'OrderController@show'
        ]);

        Route::get('/changes/customer', [
            'as' => 'orders.customer.changes',
            'uses' => 'OrderController@customerOrderChanges'
        ]);

        Route::get('active', [
            'as' => 'orders.active',
            'uses' => 'OrderController@orderActive'
        ]);

        Route::get('previous', [
            'as' => 'orders.previous',
            'uses' => 'OrderController@previousOrders'
        ]);
    });
});