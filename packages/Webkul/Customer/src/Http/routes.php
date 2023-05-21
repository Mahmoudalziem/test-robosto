<?php

// use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'api/customer', 'middleware' => ['api']], function () {

    // Get Avatars
    Route::group(['namespace' => 'Webkul\Customer\Http\Controllers'], function () {

        // app info
        Route::get('app-info', [
            'as' => 'app.customer.app-info',
            'uses' => 'AppInfoController@get'
        ]);
        // Avatars
        Route::group(['prefix' => 'avatars'], function () {
            Route::get('get', [
                'as' => 'avatars.get',
                'uses' => 'AvatarController@getAvatars'
            ]);
            Route::post('create', [
                'as' => 'avatars.create',
                'uses' => 'AvatarController@create'
            ]);
            Route::post('delete/{id}', [
                'as' => 'avatars.delete',
                'uses' => 'AvatarController@delete'
            ]);
        });

        // Address Icons
        Route::group(['prefix' => 'address-icons'], function () {
            Route::get('get', [
                'as' => 'address-icons.get',
                'uses' => 'AddressIconsController@getIcons'
            ]);
            Route::post('create', [
                'as' => 'address-icons.create',
                'uses' => 'AddressIconsController@create'
            ]);
            Route::post('delete/{id}', [
                'as' => 'address-icons.delete',
                'uses' => 'AddressIconsController@delete'
            ]);
        });

        // Banners
        Route::get('banners/{section}', [
            'as' => 'app.customer.banner.list',
            'uses' => 'BannerController@list'
        ])->middleware('shadow.area');
    });

    // Customer Auth Routes
    Route::group(['namespace' => 'Webkul\Customer\Http\Controllers\Auth'], function () {

        Route::post('auth/register', [
            'as' => 'app.customer.register',
            'uses' => 'RegisterController@register'
        ]);

        Route::post('auth/checkOtp', [
            'as' => 'app.customer.checkOtp',
            'uses' => 'CheckOtpController@checkOtp'
        ]);

        Route::post('auth/login', [
            'as' => 'app.customer.login',
            'uses' => 'LoginController@login'
        ])->middleware('customer.must.be.active');

        Route::post('auth/logout', [
            'as' => 'app.customer.logout',
            'uses' => 'LoginController@logout'
        ])->middleware('auth:customer');
        ;
    });

    Route::group([
        'namespace' => 'Webkul\Customer\Http\Controllers',
        'middleware' => [
            'auth:customer', 'customer.must.be.active',
        ]
            ], function () {

                // get Customer info
                Route::get('/', [
                    'as' => 'app.customer.profile',
                    'uses' => 'CustomerController@profile'
                ]);

                // get Customer favorite products
                Route::get('/favorite-products', [
                    'as' => 'app.customer.favorite-products',
                    'uses' => 'CustomerController@favoriteProducts'
                ]);
                Route::post('/favorite-product', [
                    'as' => 'app.customer.favorite-products-update',
                    'uses' => 'CustomerController@updateCustomerFavoriteProductStatus'
                ]);
                
                // delete account 
                Route::post('/delete-account', [
                    'as' => 'app.customer.deleteaccount',
                    'uses' => 'CustomerController@deleteAccount'
                ]);
                Route::get('/wallet', [
                    'as' => 'app.customer.wallet',
                    'uses' => 'CustomerController@getCustomerWallet'
                ]);

                Route::put('/update', [
                    'as' => 'app.customer.update',
                    'uses' => 'CustomerController@update'
                ]);

                // address
                Route::get('addresses/', [
                    'as' => 'app.customer.address.list',
                    'uses' => 'AddressController@list'
                ]);

                Route::get('addresses/{address}', [
                    'as' => 'app.customer.address.show',
                    'uses' => 'AddressController@show'
                ]);

                Route::post('addresses/', [
                    'as' => 'app.customer.address.add',
                    'uses' => 'AddressController@add'
                ]);

                Route::put('addresses/{address}', [
                    'as' => 'app.customer.address.update',
                    'uses' => 'AddressController@update'
                ]);

                Route::delete('addresses/{address}', [
                    'as' => 'app.customer.address.delete',
                    'uses' => 'AddressController@delete'
                ]);

                // Customer Settings
                Route::get('setting', [
                    'as' => 'app.customer.settings.get',
                    'uses' => 'CustomerSettingController@get'
                ]);

                Route::put('setting/update', [
                    'as' => 'app.customer.settings.update',
                    'uses' => 'CustomerSettingController@update'
                ]);

                // Customer Cards/Visa
                Route::get('cards', [
                    'as' => 'app.customer.cards.get',
                    'uses' => 'PaymentController@getCards'
                ]);

                Route::post('addCard', [
                    'as' => 'app.customer.cards.add',
                    'uses' => 'PaymentController@addCard'
                ]);

                // Order Routes
                Route::group(['prefix' => 'order'], function () {
                    Route::post('rating', [
                        'as' => 'orders.customer.rating',
                        'uses' => 'CustomerController@customeRratingOrder'
                    ]);

                    Route::get('get-changes', [
                        'as' => 'orders.customer.get-changes',
                        'uses' => 'CustomerController@getOrderChanges'
                    ]);

                    Route::get('/changes-response', [
                        'as' => 'orders.customer.changes-response',
                        'uses' => 'CustomerController@orderChangesResponse'
                    ]);

                    Route::post('cancelled', [
                        'as' => 'orders.customer.cancelled',
                        'uses' => 'CustomerController@customerCancelOrder'
                    ]);
                });

                // Payment Paymob Routes
                Route::group(['prefix' => 'payment/paymob'], function () {

                    // paymob test api flow
                    // Payment API Flow
                    Route::post('authentication-request', [
                        'as' => 'app.payment.authentication-request',
                        'uses' => 'PaymentController@authenticationRequest'
                    ]);

                    Route::post('order-registration-api', [
                        'as' => 'app.payment.order-registration-api',
                        'uses' => 'PaymentController@orderRegistrationAPI'
                    ]);

                    Route::post('payment-key-request', [
                        'as' => 'app.payment.payment-key-request',
                        'uses' => 'PaymentController@paymentKeyRequest'
                    ]);
                    /////////////////////////////////////////////////////

                    Route::post('generate-iframe', [
                        'as' => 'app.payment.generate-iframe',
                        'uses' => 'PaymentController@generateIFrame'
                    ]);

                    Route::get('list-cards', [
                        'as' => 'app.payment.list-card',
                        'uses' => 'PaymentController@listCards'
                    ]);

                    Route::delete('delete-card', [
                        'as' => 'app.payment.delete-card',
                        'uses' => 'PaymentController@deleteCard'
                    ]);

                    Route::post('charge-via-card-token', [
                        'as' => 'app.payment.charge-via-card-token',
                        'uses' => 'PaymentController@chargeViaCardToken'
                    ]);
                });
            });

    // Payment Paymob Routes
    Route::group(['prefix' => 'payment/paymob', 'namespace' => 'Webkul\Customer\Http\Controllers'], function () {

        //Transaction processed callback
        Route::post('/transaction-processed-callback', [
            'as' => 'payment.transaction-processed-callback',
            'uses' => 'PaymentController@transactionProcessedCallback'
        ]);

        Route::get('/transaction-response-callback', [
            'as' => 'payment.transaction-response-callback',
            'uses' => 'PaymentController@transactionResponseCallback'
        ]);
    });
});

