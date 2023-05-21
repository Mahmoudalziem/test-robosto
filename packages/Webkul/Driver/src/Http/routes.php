<?php

Route::group(['prefix' => 'api/driver', 'middleware' => ['api']], function () {


    Route::group(['namespace' => 'Webkul\Driver\Http\Controllers\Auth'], function ($router) {
        Route::post('login', 'LoginController@login')->name('app.driver.login')->middleware('driver.must.be.active');
    });

    Route::group(['namespace' => 'Webkul\Driver\Http\Controllers', 'middleware' => ['auth:driver','driver.must.be.active'],], function ($router) {
        // logout
        Route::post('logout', 'Auth\LoginController@logout');

        Route::get('profile', 'DriverController@profile');
        Route::post('motor-log', 'DriverController@motorLog');
        Route::post('set-status-log', 'DriverController@setStatusLog');
        Route::get('motors', 'DriverController@getMotors');
        Route::post('request/break', 'DriverController@requestBreak');
        Route::post('request/emergency', 'DriverController@requestEmergency');
        Route::post('confirm-at-warehouse', 'DriverController@confirmAtWarehouse');
        Route::get('wallet', 'DriverController@driverWallet');
        Route::post('deliverMoney', 'DriverController@deliverMoney');
        Route::post('complete-transaction-request', 'DriverController@completeTransactionRequest');
        Route::post('cancel-transaction-request', 'DriverController@cancelTransactionRequest');
        Route::get('incentives', [
            'as' => 'driver.incentives',
            'uses' => 'DriverController@incentives'
        ]);

        // Order Routes
        Route::group(['prefix' => 'order'], function () {
            Route::get('new', [
                'as' => 'orders.new.driver.response',
                'uses' => 'DriverController@driverNewOrderResponse'
            ]);

            Route::get('return', [
                'as' => 'orders.return.driver.response',
                'uses' => 'DriverController@driverReturnOrderResponse'
            ]);

            Route::get('confirm-receiving-items', [
                'as' => 'orders.driver.confirm-receiving-items',
                'uses' => 'DriverController@driverConfirmReceivingItems'
            ]);
            Route::get('prioritize-order', [
                'as' => 'orders.driver.prioritize-ontheway-order',
                'uses' => 'DriverController@prioritizeOnTheWayOrder'
            ]);
            Route::get('confirm-receiving-return-items-from-customer', [
                'as' => 'orders.driver.confirm-receiving-return-items-from-customer',
                'uses' => 'DriverController@driverConfirmReceivingReturnItemsFromCustomer'
            ]);

            Route::get('at_place', [
                'as' => 'orders.driver.at-place',
                'uses' => 'DriverController@driverOrderAtPlace'
            ]);

            Route::get('delivered', [
                'as' => 'orders.driver.delivered',
                'uses' => 'DriverController@driverOrderDelivered'
            ]);

            Route::get('history', [
                'as' => 'driver.orders.history',
                'uses' => 'DriverController@ordersHistory'
            ]);

            // Customer Cancel Order Before Receiving
            Route::get('customer-returned-order', [
                'as' => 'driver.orders.customer-returned-order',
                'uses' => 'DriverController@customerReturnedOrder'
            ]);

            // Driver Confirm Receiving to Warehouse
            Route::get('reached-to-warehouse', [
                'as' => 'driver.orders.reached-to-warehouse',
                'uses' => 'DriverController@reachedToWarehouse'
            ]);

            // Customer Updeted Order Before Receiving
            Route::post('customer-updated-order', [
                'as' => 'driver.orders.customer-updated-order',
                'uses' => 'DriverController@customerUpdatedOrder'
            ]);

            // Customer Updeted Order Before Receiving
            Route::post('customer-updated-order', [
                'as' => 'driver.orders.customer-updated-order',
                'uses' => 'DriverController@customerUpdatedOrder'
            ]);

            Route::get('current', [
                'as' => 'driver.current-order',
                'uses' => 'DriverController@currentOrder'
            ]);

            Route::get('active-orders', [
                'as' => 'driver.active-orders',
                'uses' => 'DriverController@activeOrders'
            ]);

            Route::get('v2/current', [
                'as' => 'driver.current-order',
                'uses' => 'V2\DriverController@currentOrder'
            ]);

            Route::get('v2/active-orders', [
                'as' => 'driver.active-orders',
                'uses' => 'V2\DriverController@activeOrders'
            ]);

            Route::get('start-delivery', [
                'as' => 'driver.start-delivery',
                'uses' => 'DriverController@startDelivery'
            ]);
        });
    });
});



