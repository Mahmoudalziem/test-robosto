<?php

Route::group(['prefix' => 'api/collector', 'middleware' => ['api']], function () {

    Route::group(['namespace' => 'Webkul\Collector\Http\Controllers'], function () {
        // Collector Auth Routes
        Route::group(['namespace' => 'Auth'], function () {
            Route::post('auth/login', [
                'as' => 'collector.login',
                'uses' => 'LoginController@login'
            ]);
        });

        // protected routes for Collector App
        Route::group(['middleware' => 'auth:collector'], function () {

            Route::post('auth/logout', 'Auth\LoginController@logout');

            // collector profile
            Route::get('/profile', [
                'as' => 'collector.profile',
                'uses' => 'CollectorController@profile'
            ]);

            Route::get('current-order', [
                'as' => 'collector.current-order',
                'uses' => 'CollectorController@currentOrder'
            ]);

            Route::get('order-by-id', [
                'as' => 'collector.order-by-id',
                'uses' => 'CollectorController@orderById'
            ]);
            
            Route::get('order-ready-to-pickup', [
                'as' => 'collector.order-ready-to-pickup',
                'uses' => 'CollectorController@orderReadyToPickup'
            ]);

            Route::get('orders/archived', [
                'as' => 'collector.order.archived',
                'uses' => 'CollectorController@archivedOrders'
            ]);

            // Inventory Warehouse
            Route::get('inventory/products', [
                'as' => 'collector.inventory-list-products',
                'uses' => 'CollectorController@inventoryProductsList'
            ]);

            Route::get('inventory/products/{product}', [
                'as' => 'collector.inventory-product-show',
                'uses' => 'CollectorController@inventoryProductShow'
            ]);

            // tasks(transfers || Adustments)
            Route::get('tasks/', [
                'as' => 'collector.tasks-list',
                'uses' => 'CollectorController@tasks'
            ]);

            Route::get('tasks/{id}', [
                'as' => 'collector.tasks-show',
                'uses' => 'CollectorController@show'
            ]);

            Route::post('inventory/confirm-transaction/{id}', [
                'as' => 'collector.inventory-confirm-transaction',
                'uses' => 'CollectorController@confirmTransaction'
            ]);

            Route::get('confirm-returned-order-received', [
                'as' => 'collector.confirm-returned-order-received',
                'uses' => 'CollectorController@confirmReturnedOrderReceived'
            ]);

            Route::post('confirm-returned-items-received', [
                'as' => 'collector.confirm-returned-items-received',
                'uses' => 'CollectorController@confirmReturnedItemsReceived'
            ]);
            
            Route::post('confirm-returned-items-received', [
                'as' => 'collector.confirm-returned-items-received-oneHoure',
                'uses' => 'CollectorController@confirmReturnedItemsReceivedOneHoure'
            ]);

            Route::get('orders/return', [
                'as' => 'orders.return.collector.response',
                'uses' => 'CollectorController@collectorReturnOrderResponse'
            ]);

            // collector start-inventory-control
            Route::post('start-inventory-control', [
                'as' => 'collector.start.inventory.control',
                'uses' => 'CollectorController@startInventoryControl'
            ]);

            // collector check active inventory control
            Route::post('check-inventory-control', [
                'as' => 'collector.check.inventory.control',
                'uses' => 'CollectorController@checkInventoryControl'
            ]);

            // invetory validation
            Route::get('scan-item', [
                'as' => 'collector.scan.item',
                'uses' => 'CollectorController@scanItem'
            ]);

            // collector post stock of item in the wherehouse
            Route::post('post-item-stock', [
                'as' => 'collector.post.item.stock',
                'uses' => 'CollectorController@postItemStock'
            ]);

            // collector start-inventory-control
            Route::post('end-inventory-control', [
                'as' => 'collector.end.inventory.control',
                'uses' => 'CollectorController@endInventoryControl'
            ]);
        });
    });
});
