<?php

use Illuminate\Support\Facades\Route;


Route::group(['prefix' => 'api/shipping', 'middleware' => ['api']], function () {

    // Auth Routes
    Route::group(['prefix' => 'auth', 'namespace' => 'Webkul\Shipping\Http\Controllers\Api\Admin\V1'], function () {
        Route::post('/login', 'AuthController@login');
        Route::get('/me', 'AuthController@me')->middleware('auth:shipper');
    });

    Route::group(['prefix' => 'address', 'namespace' => 'Webkul\Shipping\Http\Controllers\Api\Admin\V1'], function () {
        Route::post('/create-shipping-address', [
            'as' => 'shipping.address.create',
            'uses' => 'ShippingController@createCustomerShippingAddress'
        ]);
    });
    Route::middleware('auth:shipper')->group(function () {
        Route::group(['prefix' => 'locations', 'namespace' => 'Webkul\Shipping\Http\Controllers\Api\Admin\V1'], function () {
            Route::post('/create', [
                'as' => 'shipping.pickuplocation.create',
                'uses' => 'ShippingController@createPickupLocation'
            ]);
            Route::get('/list', [
                'as' => 'shipping.pickuplocation.show',
                'uses' => 'ShippingController@showPickupLocations'
            ]);
        });
    });

    Route::middleware('auth:shipper')->group(function () {
        Route::group(['prefix' => 'shipment', 'namespace' => 'Webkul\Shipping\Http\Controllers\Api\Admin\V1'], function () {
            Route::post('/create', [
                'as' => 'shipping.shippment.create',
                'uses' => 'ShippingController@createShippment'
            ]);
            Route::post('/create-many', [
                'as' => 'shipping.shippment.create',
                'uses' => 'ShippingController@createManyShippments'
            ]);
            Route::get('/list', [
                'as' => 'shipping.shippmenttransfer.all',
                'uses' => 'ShippingController@listShippments'
            ]);
            Route::get('/show/{id}', [
                'as' => 'shipping.shippmenttransfer.profile',
                'uses' => 'ShippingController@shippmentProfile'
            ]);
            Route::get('/cancel/{id}', [
                'as' => 'shipping.shippershipment.cancel',
                'uses' => 'ShippingController@cancelShipperShippment'
            ]);
            Route::post('/update-price', [
                'as' => 'shipping.shippershipment.update-price',
                'uses' => 'ShippingController@updateShippmentPrice'
            ]);
            Route::get('/export', [
                'as' => 'shipping.shippershipment.export',
                'uses' => 'ShippingController@exportShipper'
            ]);
        });
    });

    Route::middleware('auth:admin')->group(function () {
        Route::group(['prefix' => 'transfer', 'namespace' => 'Webkul\Shipping\Http\Controllers\Api\Admin\V1'], function () {
            Route::get('/list', [
                'as' => 'shipping.shippmenttransfer.all',
                'uses' => 'ShippingController@listShippmentTransfers'
            ]);
            Route::get('/show/{id}', [
                'as' => 'shipping.shippmenttransfer.profile',
                'uses' => 'ShippingController@shippmentTransferProfile'
            ]);
            Route::put('/set-status/{id}', [
                'as' => 'shipping.shippmenttransfer.updatestatus',
                'uses' => 'ShippingController@setStatus'
            ]);
        });
        Route::group(['prefix' => 'bulk-transfer', 'namespace' => 'Webkul\Shipping\Http\Controllers\Api\Admin\V1'], function () {
            Route::post('/create', [
                'as' => 'admin.shipping.bulktransfer.create',
                'uses' => 'ShippingController@createBulkTransfer'
            ]);
            Route::get('/list', [
                'as' => 'admin.shipping.bulktransfer.index',
                'uses' => 'ShippingController@listShippmentBulkTransfers'
            ]);
            Route::get('/show/{id}', [
                'as' => 'admin.shipping.bulktransfer.show',
                'uses' => 'ShippingController@shippmentBulkTransferProfile'
            ]);
            Route::put('/set-status/{id}', [
                'as' => 'admin.shipping.bulktransfer.updatestatus',
                'uses' => 'ShippingController@setBulkTransferStatus'
            ]);
            Route::get('/list-transferable-shipments', [
                'as' => 'admin.shipping.bulktransfer.listshipable',
                'uses' => 'ShippingController@getDistibutableShippments'
            ]);
        });
        Route::group(['prefix' => 'shipment', 'namespace' => 'Webkul\Shipping\Http\Controllers\Api\Admin\V1'], function () {
            Route::get('/admin/list', [
                'as' => 'shipping.shippmenttransfer.all',
                'uses' => 'ShippingController@listAllShippments'
            ]);
            Route::get('/admin/show/{id}', [
                'as' => 'shipping.shippmenttransfer.profile',
                'uses' => 'ShippingController@shippmentFullProfile'
            ]);
            Route::get('/admin/cancel/{id}', [
                'as' => 'shipping.adminshipment.cancel',
                'uses' => 'ShippingController@cancelShippment'
            ]);
            Route::get('/admin/export', [
                'as' => 'shipping.shippershipment.export',
                'uses' => 'ShippingController@exportAdmin'
            ]);
            Route::get('/admin/reset-customer-info/{id}', [
                'as' => 'shipping.adminshipment.resetinfo',
                'uses' => 'ShippingController@resetCustomerInfo'
            ]);

            Route::post('/admin/redispatch-order/{id}', [
                'as' => 'shipping.adminshipment.redispatchorder',
                'uses' => 'ShippingController@redispatchNewDeliveryOrder'
            ]);
            Route::post('/admin/redispatch-pickup/{id}', [
                'as' => 'shipping.adminshipment.redispatchpickup',
                'uses' => 'ShippingController@redispatchPickUpOrder'
            ]);
            Route::post('/admin/picked-up/{id}', [
                'as' => 'shipping.adminshipment.pickedup',
                'uses' => 'ShippingController@markShippmentAsPickedUp'
            ]);
            Route::post('/admin/mark-as-pending-distribution/{id}', [
                'as' => 'shipping.adminshipment.redispatchorder',
                'uses' => 'ShippingController@markShippmentsAsPendingDistribution'
            ]);
            Route::post('/admin/dispatch-pending-distribution/{id}', [
                'as' => 'shipping.adminshipment.redispatchorder',
                'uses' => 'ShippingController@dispatchPendingDistributionShippments'
            ]);

            Route::post('/admin/return-to-vendor/{id}', [
                'as' => 'shipping.adminshipment.redispatchorder',
                'uses' => 'ShippingController@returnToVendor'
            ]);

            Route::post('/admin/settle/{id}', [
                'as' => 'shipping.adminshipment.settleshippment',
                'uses' => 'ShippingController@settleShippment'
            ]);
            Route::post('/admin/rts/{id}', [
                'as' => 'shipping.adminshipment.rtsshippment',
                'uses' => 'ShippingController@rtsShippment'
            ]);
        });
        Route::group(['prefix' => 'shipper', 'namespace' => 'Webkul\Shipping\Http\Controllers\Api\Admin\V1'], function () {
            Route::get('list', [
                'as' => 'shipping.shippers.list',
                'uses' => 'ShippingController@listShippers'
            ]);
            Route::post('create', [
                'as' => 'shipping.shippers.create',
                'uses' => 'ShippingController@createShipper'
            ]);
        });

    });


    Route::middleware('auth:shipper')->group(function () {
        // Dashboard Routes
        Route::group(['prefix' => 'dashboard', 'namespace' => 'Webkul\Shipping\Http\Controllers\Api\Admin\V1'], function () {
            Route::get('/shipments-overview', 'DashboardController@shippmentsOverview');
        });
        // Core Routes
        Route::group(['prefix' => 'core', 'namespace' => 'Webkul\Shipping\Http\Controllers\Api\Admin\V1'], function () {
            Route::get('areas', 'DashboardController@areaList')->name('shipping.core.area.list');
        });
        Route::get('fetchAll/{type}', 'Webkul\Admin\Http\Controllers\CommonController@fetchAll')->name('shipping.core.fetchAll');
    });
});
