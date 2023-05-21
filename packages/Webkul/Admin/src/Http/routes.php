<?php

use Illuminate\Support\Facades\Route;
use Webkul\Inventory\Models\InventoryTransaction;

Route::group(['prefix' => 'api/admin', 'middleware' => ['api']], function () {


    // Login Routes
    //login post route to admin auth controller
    Route::post('/auth/login', 'Webkul\User\Http\Controllers\Api\AuthController@login')->middleware('admin.must.be.active')->name('admin.api.login');

    // Forget Password Routes
    Route::post('/auth/forget-password/check-email', 'Webkul\User\Http\Controllers\Api\ForgetPasswordController@checkEmail')->name('admin.api.forget-password.check-email');
    Route::post('/auth/forget-password/check-otp', 'Webkul\User\Http\Controllers\Api\ForgetPasswordController@checkOTP')->name('admin.api.forget-password.check-otp');
    Route::post('/auth/forget-password/reset', 'Webkul\User\Http\Controllers\Api\ForgetPasswordController@resetPassword')->name('admin.api.forget-password.reset');

    // Admin Routes
    Route::group(
            ['middleware' => ['auth:admin', 'admin.must.be.active', 'admin.permission.gate']],
            function () {

                // *******************            
                // Dashboard 
                // *******************
                // Dashboard Route

                Route::group(['prefix' => 'dashboard', 'namespace' => 'Webkul\Admin\Http\Controllers'], function () {

                    Route::get('orders-overview', 'DashboardController@index')->name('admin.dashboard.orders-overview.summary');
                    Route::get('map', 'DashboardController@getMapData')->name('admin.dashboard.map.summary');
                    Route::get('overview', 'DashboardController@getMapData')->name('admin.dashboard.overview.summary');

                    Route::get('total-stores', 'DashboardController@totalStores')->name('admin.dashboard.overview.overview-summary.totalStores');
                    Route::get('total-categories', 'DashboardController@totalCategories')->name('admin.dashboard.overview.overview-summary.totalCategories');
                    Route::get('total-items', 'DashboardController@totalItems')->name('admin.dashboard.overview.overview-summary.totalItems');
                    Route::get('items-expired-soon', 'DashboardController@itemsExpiredSoon')->name('admin.dashboard.overview.overview-summary.itemsExpiredSoon');
                    Route::get('items-out-of-stock', 'DashboardController@itemsOutOfStock')->name('admin.dashboard.overview.overview-summary.itemsOutOfStock');
                    Route::get('avg-orders-price', 'DashboardController@avgOrdersPrice')->name('admin.dashboard.overview.overview-summary.avgOrdersPrice');
                    Route::get('valid-promotions', 'DashboardController@validPromotions')->name('admin.dashboard.overview.overview-summary.validPromotions');

                    Route::get('category-sold-products/{dir}', 'DashboardController@categorySoldProducts')->name('admin.dashboard.overview.ranking-data.category');
                    Route::get('list-sold-products/{dir}', 'DashboardController@soldProductsSorted')->name('admin.dashboard.overview.ranking-data.products');
                    Route::get('area-orders/{dir}', 'DashboardController@areaOrders')->name('admin.dashboard.overview.ranking-data.areas');
                    Route::get('store-orders/{dir}', 'DashboardController@storeOrders')->name('admin.dashboard.overview.ranking-data.stores');
                    Route::get('exp-date-of-items/{dir}', 'DashboardController@expDateOfItems')->name('admin.dashboard.overview.ranking-data.exp-date-of-items');

                    Route::get('item-quantity/{dir}', 'DashboardController@itemQuantity')->name('admin.dashboard.overview.ranking-data.itemQuantity');
                    Route::get('product-visits-count/{dir}', 'DashboardController@productVisitsCount')->name('admin.dashboard.overview.ranking-data.no-of-visits');
                    Route::get('product-visits-count-per-customer/{dir}', 'DashboardController@productVisitsCountPerCustomer')->name('admin.dashboard.overview.ranking-data.no-of-visits-per-customer');
                    
                    Route::get('items-out-of-stock-area/{id}', 'DashboardController@itemsOutOfStockByArea')->name('admin.dashboard.overview.overview-summary.itemsOutOfStockByArea');
                });
                // ======================================================================== 
                // *******************            
                // Customer 
                // *******************
                // Customer Routes
                Route::group(['prefix' => 'customers', 'namespace' => 'Webkul\Admin\Http\Controllers\Customer'], function () {
                    //Customer Management Routes
                    Route::get('list', 'CustomerController@list')->name('admin.customers.customer.index');
                    Route::get('export/', 'CustomerController@export')->name('admin.customers.customer.export');
                    Route::post('/', 'CustomerController@add')->name('admin.customers.customer.store');
                    Route::get('{customer}', 'CustomerController@show')->name('admin.customers.customer.show');
                    Route::put('{customer}', 'CustomerController@update')->name('admin.customers.customer.update');
                    Route::put('update-name/{customer}', 'CustomerController@updateCustomerName')->name('admin.customers.customer.updateCustomerName');
                    Route::put('set-status/{customer}', 'CustomerController@setStatus')->name('admin.customers.customer.update-status');
                    Route::post('callcenter-check-phone', 'CustomerController@callcenterCheckPhone')->name('admin.customers.customer.call-center-check-phone');
                    Route::put('callcenter-update-profile/{customer}', 'CustomerController@callcenterUpdateProfile')->name('admin.customers.customer.call-center-update-profile');
                    Route::get('addresses/list/{customer}', 'CustomerController@addressesList')->name('admin.customers.address.index');
                    Route::get('addresses/{address}', 'CustomerController@addressShow')->name('admin.customers.address.show');
                    Route::post('addresses', 'CustomerController@addressAdd')->name('admin.customers.address.store');
                    Route::put('addresses/{address}', 'CustomerController@addressUpdate')->name('admin.customers.address.update');
                    Route::delete('addresses/{address}', 'CustomerController@addressDelete')->name('admin.customers.address.delete');
                    Route::get('invitations-logs/{customer}', 'CustomerController@invitationsLogs')->name('admin.customers.customer.invitations-logs');
                    Route::get('orders/{customer}', 'CustomerController@orders')->name('admin.customers.customer.orders');
                    Route::post('note/create', 'CustomerController@noteCreate')->name('admin.customers.customer.note-create');
                    Route::get('note/list', 'CustomerController@noteList')->name('admin.customers.customer.note-list');
                    Route::post('group/create', 'CustomerController@createGroup')->name('admin.customers.customer.create-group');
                    Route::post('group/update', 'CustomerController@updateGroup')->name('admin.customers.customer.update-group');
                    Route::put('update-wallet/{customer}', 'CustomerController@updateCustomerWallet')->name('admin.customers.customer.updateCustomerWallet');
                    Route::get('payment-cards/{customer}', 'CustomerController@paymentCardsList')->name('admin.customers.customer.payment-cards-list');                    
                    Route::put('payment-cards/{card}', 'CustomerController@deletePaymentCard')->name('admin.customers.customer.delete-payment-card');                                        
                    Route::get('call-with-otp/{customer}', 'CustomerController@callWithOtp')->name('admin.customers.customer.call-with-otp');
                    Route::post('send-sms-to-customer/{customer}', 'CustomerController@sendSmsToCustomer')->name('admin.customers.customer.send-sms-to-customer');                    
                    Route::get('wallet-reason/list', 'CustomerController@walletReasonList')->name('admin.customers.customer.wallet-reason-list');
                    Route::get('devices/list', 'CustomerController@devicesList')->name('admin.customers.customer.device-list');
                });
                // ======================================================================== 
                // *******************            
                // App Management 
                // *******************
                // Banner Routes
                Route::group(['prefix' => 'banners', 'namespace' => 'Webkul\Admin\Http\Controllers\Banner'], function () {
                    Route::get('/{section}', [// {sale} or {deal}
                        'as' => 'admin.app-management.banners.index',
                        'uses' => 'BannerController@list'
                    ]);
                    Route::get('/show/{banner}', [
                        'as' => 'admin.app-management.banners.show',
                        'uses' => 'BannerController@show'
                    ]);
                    Route::post('/', [
                        'as' => 'admin.app-management.banners.store',
                        'uses' => 'BannerController@add'
                    ]);

                    Route::put('/{banner}', [
                        'as' => 'admin.app-management.banners.update',
                        'uses' => 'BannerController@update'
                    ]);

                    Route::delete('/{banner}', [
                        'as' => 'admin.app-management.banners.delete',
                        'uses' => 'BannerController@delete'
                    ]);

                    Route::put('/set-status/{banner}', [// {sale} or {deal}
                        'as' => 'admin.app-management.banners.update-status',
                        'uses' => 'BannerController@setStatus'
                    ]);

                    Route::put('/set-default/{banner}', [// {sale} or {deal}
                        'as' => 'admin.app-management.banners.update-default',
                        'uses' => 'BannerController@setDefault'
                    ]);

                    Route::put('/set-position/{banner}', [// {sale} or {deal}
                        'as' => 'admin.app-management.banners.update-position',
                        'uses' => 'BannerController@setPosition'
                    ]);
                });

                // Category Routes
                Route::group(['prefix' => 'categories', 'namespace' => 'Webkul\Admin\Http\Controllers\Category'], function () {

                    Route::get('/', [
                        'as' => 'admin.app-management.categories.index',
                        'uses' => 'CategoryController@index'
                    ]);

                    Route::post('create', [
                        'as' => 'admin.app-management.categories.store',
                        'uses' => 'CategoryController@create'
                    ]);

                    Route::get('show/{id}', [
                        'as' => 'admin.app-management.categories.show',
                        'uses' => 'CategoryController@show'
                    ]);

                    Route::post('update/{id}', [
                        'as' => 'admin.app-management.categories.update',
                        'uses' => 'CategoryController@update'
                    ]);

                    Route::post('update-status/{id}', [
                        'as' => 'admin.app-management.categories.update-status',
                        'uses' => 'CategoryController@updateStatus'
                    ]);

                    Route::post('delete/{id}', [
                        'as' => 'admin.app-management.categories.delete',
                        'uses' => 'CategoryController@delete'
                    ]);

                    Route::get('{id}/subcategories', [
                        'as' => 'admin.app-management.categories.subcategories-list',
                        'uses' => 'CategoryController@listSubCategoyByCategory'
                    ]);

                    Route::get('subcategories/{id}/products', [
                        'as' => 'admin.app-management.categories.subcategories-products-list',
                        'uses' => 'CategoryController@listProductsBySubcategory'
                    ]);
                });

                // Sub Category Routes
                Route::group(['prefix' => 'sub-categories', 'namespace' => 'Webkul\Admin\Http\Controllers\Category'], function () {
                    Route::get('/', [
                        'as' => 'admin.app-management.sub-categories.index',
                        'uses' => 'SubCategoryController@index'
                    ]);

                    Route::post('create', [
                        'as' => 'admin.app-management.sub-categories.store',
                        'uses' => 'SubCategoryController@create'
                    ]);

                    Route::get('show/{id}', [
                        'as' => 'admin.app-management.sub-categories.show',
                        'uses' => 'SubCategoryController@show'
                    ]);

                    Route::post('update/{id}', [
                        'as' => 'admin.app-management.sub-categories.update',
                        'uses' => 'SubCategoryController@update'
                    ]);

                    Route::post('update-status/{id}', [
                        'as' => 'admin.app-management.sub-categories.update-status',
                        'uses' => 'SubCategoryController@updateStatus'
                    ]);

                    Route::post('delete/{id}', [
                        'as' => 'admin.sub-categories.delete',
                        'uses' => 'SubCategoryController@delete'
                    ]);
                });

                // Brands Routes
                Route::group(['prefix' => 'brands', 'namespace' => 'Webkul\Admin\Http\Controllers\Brand'], function () {
                    Route::get('/', [
                        'as' => 'admin.app-management.brands.index',
                        'uses' => 'BrandController@index'
                    ]);

                    Route::get('/getAllBrands', [
                        'as' => 'admin.app-management.brands.list-all',
                        'uses' => 'BrandController@getAllBrands'
                    ]);

                    Route::get('/{brand}/products', [
                        'as' => 'admin.app-management.brands.products',
                        'uses' => 'BrandController@productsByBrandId'
                    ]);

                    Route::post('create', [
                        'as' => 'admin.app-management.brands.store',
                        'uses' => 'BrandController@create'
                    ]);

                    Route::get('show/{id}', [
                        'as' => 'admin.app-management.brands.show',
                        'uses' => 'BrandController@show'
                    ]);

                    Route::post('update/{id}', [
                        'as' => 'admin.app-management.brands.update',
                        'uses' => 'BrandController@update'
                    ]);

                    Route::post('update-status/{id}', [
                        'as' => 'admin.app-management.brands.update-status',
                        'uses' => 'BrandController@updateStatus'
                    ]);

                    Route::post('delete/{id}', [
                        'as' => 'admin.app-management.brands.delete',
                        'uses' => 'BrandController@delete'
                    ]);
                });

                // Product Tags Routes
                Route::group(['prefix' => 'producttags', 'namespace' => 'Webkul\Admin\Http\Controllers\ProductTag'], function () {
                    Route::get('/', [
                        'as' => 'admin.app-management.producttags.index',
                        'uses' => 'ProductTagController@index'
                    ]);

                    Route::post('create', [
                        'as' => 'admin.app-management.producttags.store',
                        'uses' => 'ProductTagController@create'
                    ]);

                    Route::get('show/{id}', [
                        'as' => 'admin.app-management.producttags.show',
                        'uses' => 'ProductTagController@show'
                    ]);

                    Route::put('update/{id}', [
                        'as' => 'admin.app-management.producttags.update',
                        'uses' => 'ProductTagController@update'
                    ]);
                });

                // Product Labels Routes
                Route::group(['prefix' => 'productlabels', 'namespace' => 'Webkul\Admin\Http\Controllers\Productlabel'], function () {
                    Route::get('/', [
                        'as' => 'admin.app-management.productlabels.index',
                        'uses' => 'ProductlabelController@index'
                    ]);

                    Route::post('create', [
                        'as' => 'admin.app-management.productlabels.store',
                        'uses' => 'ProductlabelController@create'
                    ]);

                    Route::get('show/{id}', [
                        'as' => 'admin.app-management.productlabels.show',
                        'uses' => 'ProductlabelController@show'
                    ]);

                    Route::put('update/{id}', [
                        'as' => 'admin.app-management.productlabels.update',
                        'uses' => 'ProductlabelController@update'
                    ]);
                });

                // Products Routes
                Route::group(['prefix' => 'products', 'namespace' => 'Webkul\Admin\Http\Controllers\Product'], function () {
                    Route::get('/', [
                        'as' => 'admin.app-management.products.index',
                        'uses' => 'ProductController@index'
                    ]);

                    Route::get('/units', [
                        'as' => 'admin.app-management.products.list-units',
                        'uses' => 'ProductController@listUnits'
                    ]);

                    Route::post('create', [
                        'as' => 'admin.app-management.products.store',
                        'uses' => 'ProductController@create'
                    ]);

                    Route::get('show/{id}', [
                        'as' => 'admin.app-management.products.show',
                        'uses' => 'ProductController@show'
                    ]);

                    Route::get('card/{sku}', [
                        'as' => 'admin.app-management.sku.card',
                        'uses' => 'ProductController@skuCard'
                    ]);

                    Route::get('sku/{id}', [
                        'as' => 'admin.app-management.products.skus',
                        'uses' => 'ProductController@getSku'
                    ]);

                    Route::get('supplier/{sku}', [
                        'as' => 'admin.app-management.products.get-supplier-by-sku',
                        'uses' => 'ProductController@getSupplierBySku'
                    ]);

                    Route::post('update/{id}', [
                        'as' => 'admin.app-management.products.update',
                        'uses' => 'ProductController@update'
                    ]);

                    Route::post('update-status/{id}', [
                        'as' => 'admin.app-management.products.update-status',
                        'uses' => 'ProductController@updateStatus'
                    ]);

                    Route::post('update-note/{id}', [
                        'as' => 'admin.app-management.products.update-note',
                        'uses' => 'ProductController@updateNote'
                    ]);
                });

                Route::group(['prefix' => 'alerts', 'namespace' => 'Webkul\Admin\Http\Controllers\Alert'], function () {
                    Route::get('/me', [
                        'as' => 'admin.app-management.alerts.me',
                        'uses' => 'AlertController@me'
                    ]);

                    Route::get('/', [
                        'as' => 'admin.app-management.alerts.index',
                        'uses' => 'AlertController@index'
                    ]);

                    Route::put('/read', [
                        'as' => 'admin.app-management.alerts.read',
                        'uses' => 'AlertController@read'
                    ]);
                });

                // *******************            
                // Orders
                // *******************            
                // Orders Routes
                Route::group(['prefix' => 'orders', 'namespace' => 'Webkul\Admin\Http\Controllers\Sales'], function () {
                    Route::get('/list/{status?}', [
                        'as' => 'admin.orders.order.index',
                        'uses' => 'OrderController@index'
                    ]);

                    Route::post('/check-items', [
                        'as' => 'admin.orders.order.check-items',
                        'uses' => 'OrderController@checkItems'
                    ]);

                    Route::post('/create', [
                        'as' => 'admin.orders.order.store',
                        'uses' => 'OrderController@create'
                    ]);

                    Route::get('show/{order}', [
                        'as' => 'admin.orders.order.show',
                        'uses' => 'OrderController@show'
                    ]);

                    Route::get('re-order-details/{id}', [
                        'as' => 'admin.orders.order.re-order-details',
                        'uses' => 'OrderController@reOrderDetails'
                    ]);

                    Route::get('redispatch/{order}', [
                        'as' => 'admin.orders.order.redispatch',
                        'uses' => 'OrderController@redispatch'
                    ]);

                    Route::post('complaint', [
                        'as' => 'admin.orders.order.complaint',
                        'uses' => 'OrderController@complaint'
                    ]);

                    Route::post('note/create', [
                        'as' => 'admin.orders.order.note-create',
                        'uses' => 'OrderController@noteCreate'
                    ]);

                    Route::get('note/list', [
                        'as' => 'admin.orders.order.note-list',
                        'uses' => 'OrderController@noteList'
                    ]);

                    Route::get('violations/{order}', [
                        'as' => 'admin.orders.order.violations',
                        'uses' => 'OrderController@violationsList'
                    ]);


                    Route::get('violations', [
                        'as' => 'admin.orders.order.violationsall',
                        'uses' => 'OrderController@violationsListAll'
                    ]);

                    Route::post('violations/{order}', [
                        'as' => 'admin.orders.order.violation-create',
                        'uses' => 'OrderController@createViolation'
                    ]);

                    // get orders history by customer id
                    Route::get('history/{customer}', [
                        'as' => 'admin.orders.order.customer-orders-history',
                        'uses' => 'OrderController@customerOrdersHistoryList'
                    ]);

                    Route::post('return/', [
                        'as' => 'admin.orders.order.return',
                        'uses' => 'OrderController@callcenterReturnCustomerOrder'
                    ]);

                    Route::put('update-driver-and-schedule/{order}', [
                        'as' => 'admin.orders.order.update-driver-and-schedul',
                        'uses' => 'OrderController@updateDriverAndSchedul'
                    ]);

                    Route::put('assigned-order-to-collector/{order}', [
                        'as' => 'admin.orders.order.assigned-order-to-collector',
                        'uses' => 'OrderController@assignOrderToCollector'
                    ]);

                    Route::post('update/{order}', [
                        'as' => 'admin.orders.order.update',
                        'uses' => 'OrderController@update'
                    ]);

                    Route::get('cancel', [
                        'as' => 'admin.orders.order.cancel',
                        'uses' => 'OrderController@cancelOrder'
                    ]);
                    Route::get('dispatch-scheduled', [
                        'as' => 'admin.orders.order.dispatchscheduled',
                        'uses' => 'OrderController@dispatchScheduledOrder'
                    ]);
                    Route::get('/products/search', [
                        'as' => 'admin.orders.order.products-search',
                        'uses' => 'OrderController@productsSearch'
                    ])->middleware('shadow.area');

                    Route::get('/online-drivers', [
                        'as' => 'admin.orders.order.online-drivers',
                        'uses' => 'OrderController@onlineDrivers'
                    ]);
                    
                    Route::get('/online-collectors', [
                        'as' => 'admin.orders.order.online-collectors',
                        'uses' => 'OrderController@onlineCollectors'
                    ]);
                    
                    Route::get('order-items/{order}', [
                        'as' => 'admin.orders.order.order-items',
                        'uses' => 'OrderController@orderItems'
                    ]); 
                });
                // ========================================================================             
                // *******************            
                // Stores (Warehouses)
                // *******************            
                // ========================================================================          
                // Personal
                // *******************            
                // Driver Routes
                Route::group(['prefix' => 'drivers', 'namespace' => 'Webkul\Admin\Http\Controllers\Driver'], function () {
                    Route::get('/', [
                        'as' => 'admin.personal.drivers.index',
                        'uses' => 'DriverController@list'
                    ]);
                    
                    Route::get('/list-by-area', [
                        'as' => 'admin.personal.drivers.list-by-area',
                        'uses' => 'DriverController@listByArea'
                    ]);                    

                    Route::get('logs/login/{driverId}', [
                        'as' => 'admin.personal.drivers.logs-login',
                        'uses' => 'DriverController@logsLogin'
                    ]);
                    Route::get('logs/emergency/{driverId}', [
                        'as' => 'admin.personal.drivers.logs-emergency',
                        'uses' => 'DriverController@logsEmergency'
                    ]);
                    Route::get('logs/break/{driverId}', [
                        'as' => 'admin.personal.drivers.logs-break',
                        'uses' => 'DriverController@logsBreak'
                    ]);

                    Route::get('orders/{driverId}', [
                        'as' => 'admin.personal.drivers.orders',
                        'uses' => 'DriverController@orders'
                    ]);

                    Route::get('orders-driver-dispatching/{driverId}', [
                        'as' => 'admin.personal.drivers.orders-driver-dispatching',
                        'uses' => 'DriverController@ordersDriverDispatching'
                    ]);

                    Route::get('order-detail/{orderId}', [
                        'as' => 'admin.personal.drivers.order-detail',
                        'uses' => 'DriverController@orderDetail'
                    ]);

                    Route::get('/{driver}', [
                        'as' => 'admin.personal.drivers.show',
                        'uses' => 'DriverController@show'
                    ]);

                    Route::post('', [
                        'as' => 'admin.personal.drivers.store',
                        'uses' => 'DriverController@add'
                    ]);

                    Route::put('{driver}', [
                        'as' => 'admin.personal.drivers.update',
                        'uses' => 'DriverController@update'
                    ]);

                    Route::put('/set-status/{driver}', [// {sale} or {deal}
                        'as' => 'admin.personal.drivers.update-status',
                        'uses' => 'DriverController@setStatus'
                    ]);

                    Route::put('set-logout/{driver}', [
                        'as' => 'admin.personal.drivers.set-logout',
                        'uses' => 'DriverController@setLogout'
                    ]);

                    Route::get('avg-delivery-time/{driver}', [
                        'as' => 'admin.personal.drivers.avg-delivery-time',
                        'uses' => 'DriverController@avgDeliveryTime'
                    ]);
                    
                    Route::post('supervisor-rate/{driver}', [
                        'as' => 'admin.personal.drivers.supervisor-rate',
                        'uses' => 'DriverController@supervisorRate'
                    ]);

                    Route::get('violations/{driver}', [
                        'as' => 'admin.personal.drivers.violations',
                        'uses' => 'DriverController@violations'
                    ]);
                });

                // Collector Routes
                Route::group(['prefix' => 'collectors', 'namespace' => 'Webkul\Admin\Http\Controllers\Collector'], function () {
                    Route::get('/', [
                        'as' => 'admin.personal.collectors.index',
                        'uses' => 'CollectorController@list'
                    ]);
                    Route::get('logs/{collectorId}', [
                        'as' => 'admin.personal.collectors.logs',
                        'uses' => 'CollectorController@logs'
                    ]);
                    Route::get('orders/{collectorId}', [
                        'as' => 'admin.personal.collectors.orders',
                        'uses' => 'CollectorController@orders'
                    ]);
                    Route::get('/{collector}', [
                        'as' => 'admin.personal.collectors.show',
                        'uses' => 'CollectorController@show'
                    ]);

                    Route::post('', [
                        'as' => 'admin.personal.collectors.store',
                        'uses' => 'CollectorController@add'
                    ]);

                    Route::put('{collector}', [
                        'as' => 'admin.personal.collectors.update',
                        'uses' => 'CollectorController@update'
                    ]);
                    Route::put('/set-status/{collector}', [// {sale} or {deal}
                        'as' => 'admin.personal.collectors.update-status',
                        'uses' => 'CollectorController@setStatus'
                    ]);
                    
                    Route::put('set-logout/{collector}', [
                        'as' => 'admin.personal.collectors.set-logout',
                        'uses' => 'CollectorController@setLogout'
                    ]);

                    Route::put('/set-can-receive-orders/{collector}', [ // {sale} or {deal}
                    	'as' => 'admin.personal.collectors.update-can-receive-orders',
                    	'uses' => 'CollectorController@setCanReceiveOrders'
                    ]);                    

                    Route::get('avg-preparing-time/{collector}', [
                        'as' => 'admin.personal.collectors.avg-preparing-time',
                        'uses' => 'CollectorController@avgPreparingTime'
                    ]);
                    
                    Route::get('violations/{collector}', [
                        'as' => 'admin.personal.collectors.violations',
                        'uses' => 'CollectorController@violations'
                    ]);
                });

                // ======================================================================== 
                // *******************            
                // Marketing 
                // *******************
                // Push campaigns
                // SMS campaign

                Route::group(['prefix' => 'sms-campaign', 'namespace' => 'Webkul\Admin\Http\Controllers\SMSCampaign'], function () {

                    Route::get('/', [
                        'as' => 'admin.marketing.sms-campaign.index',
                        'uses' => 'SMSCampaignController@index'
                    ]);

                    Route::post('/', [
                        'as' => 'admin.marketing.sms-campaign.store',
                        'uses' => 'SMSCampaignController@create'
                    ]);
                });

                // Notification Routes
                Route::group(['prefix' => 'notifications', 'namespace' => 'Webkul\Admin\Http\Controllers\Notification'], function () {

                    Route::get('/', [
                        'as' => 'admin.marketing.notifications.index',
                        'uses' => 'NotificationController@list'
                    ]);
                    Route::get('/show/{id}', [
                        'as' => 'admin.marketing.notifications.show',
                        'uses' => 'NotificationController@show'
                    ]);
                    Route::post('/create', [
                        'as' => 'admin.marketing.notifications.store',
                        'uses' => 'NotificationController@create'
                    ]);

                    Route::post('update/{id}', [
                        'as' => 'admin.marketing.notifications.update',
                        'uses' => 'NotificationController@update'
                    ]);

                    Route::post('delete/{id}', [
                        'as' => 'admin.marketing.notifications.delete',
                        'uses' => 'NotificationController@delete'
                    ]);
                });
                // ======================================================================== 
                // Notification Routes        
                // Promotion Routes
                Route::group(['prefix' => 'promotions', 'namespace' => 'Webkul\Admin\Http\Controllers\Promotion'], function () {
                    Route::get('/', [// {sale} or {deal}
                        'as' => 'admin.marketing.promotions.promocodes.index',
                        'uses' => 'PromotionController@list'
                    ]);

                    Route::post('/', [
                        'as' => 'admin.marketing.promotions.promocodes.store',
                        'uses' => 'PromotionController@create'
                    ]);

                    Route::get('/show/{id}', [
                        'as' => 'admin.marketing.promotions.promocodes.show',
                        'uses' => 'PromotionController@show'
                    ]);

                    Route::put('/{id}', [
                        'as' => 'admin.marketing.promotions.promocodes.update',
                        'uses' => 'PromotionController@update'
                    ]);

                    Route::get('/customers/{id}', [// {sale} or {deal}
                        'as' => 'admin.marketing.promotions.promocodes.list-customers',
                        'uses' => 'PromotionController@promotionCustomers'
                    ]);

                    Route::put('/set-status/{id}', [//
                        'as' => 'admin.marketing.promotions.promocodes.update-status',
                        'uses' => 'PromotionController@setStatus'
                    ]);
                    
                    Route::get('/orders/{id}', [
                        'as' => 'admin.marketing.promotions.promocodes.list-orders',
                        'uses' => 'PromotionController@promotionOrders'
                    ]);                     
                });

                // Discounts Routes
                Route::group(['prefix' => 'discounts', 'namespace' => 'Webkul\Admin\Http\Controllers\Discount'], function () {
                    Route::get('/', [// {sale} or {deal}
                        'as' => 'admin.marketing.promotions.discounts.index',
                        'uses' => 'DiscountController@list'
                    ]);
                    Route::get('/show/{discount}', [
                        'as' => 'admin.marketing.promotions.discounts.show',
                        'uses' => 'DiscountController@show'
                    ]);
                    Route::post('/', [
                        'as' => 'admin.marketing.promotions.discounts.store',
                        'uses' => 'DiscountController@create'
                    ]);

                    Route::put('/{id}', [
                        'as' => 'admin.marketing.promotions.discounts.update',
                        'uses' => 'DiscountController@update'
                    ]);

                    Route::put('/set-status/{id}', [//
                        'as' => 'admin.marketing.promotions.discounts.update-status',
                        'uses' => 'DiscountController@setStatus'
                    ]);
                });

                // Bundles Routes
                Route::group(['prefix' => 'bundles', 'namespace' => 'Webkul\Admin\Http\Controllers\Bundle'], function () {
                    Route::get('/', [
                        'as' => 'admin.marketing.promotions.bundles.index',
                        'uses' => 'BundleController@index'
                    ]);
                    
                    Route::get('/products/search', [
                        'as' => 'admin.marketing.promotions.bundles.products-search',
                        'uses' => 'BundleController@productsSearch'
                    ]);                    

                    Route::post('create', [
                        'as' => 'admin.marketing.promotions.bundles.store',
                        'uses' => 'BundleController@create'
                    ]);

                    Route::get('show/{id}', [
                        'as' => 'admin.marketing.promotions.bundles.show',
                        'uses' => 'BundleController@show'
                    ]);

                    Route::post('update/{id}', [
                        'as' => 'admin.marketing.promotions.bundles.update',
                        'uses' => 'BundleController@update'
                    ]);

                    Route::post('update-status/{id}', [
                        'as' => 'admin.marketing.promotions.bundles.update-status',
                        'uses' => 'BundleController@updateStatus'
                    ]);

                    Route::post('delete/{id}', [
                        'as' => 'admin.marketing.promotions.bundles.delete',
                        'uses' => 'BundleController@delete'
                    ]);
                });

                // Purchase Order Routes
                Route::group(['prefix' => 'purchase-order', 'namespace' => 'Webkul\Admin\Http\Controllers\PurchaseOrder'], function () {
                    Route::get('/', [
                        'as' => 'admin.inventory.purchase-order.index',
                        'uses' => 'PurchaseOrderController@index'
                    ]);

                    Route::get('/search', [
                        'as' => 'admin.inventory.purchase-order.search',
                        'uses' => 'PurchaseOrderController@search'
                    ]);

                    Route::post('create', [
                        'as' => 'admin.inventory.purchase-order.store',
                        'uses' => 'PurchaseOrderController@create'
                    ]);
                    
                    Route::post('save-draft', [
                        'as' => 'admin.inventory.purchase-order.draft-only',
                        'uses' => 'PurchaseOrderController@draftOnly'
                    ]);                    

                    Route::get('show/{id}', [
                        'as' => 'admin.inventory.purchase-order.show',
                        'uses' => 'PurchaseOrderController@show'
                    ]);

                    Route::get('export/{id}', [
                        'as' => 'admin.inventory.purchase-order.export',
                        'uses' => 'PurchaseOrderController@export'
                    ]);

                    Route::post('update/{id}', [
                        'as' => 'admin.inventory.purchase-order.update',
                        'uses' => 'PurchaseOrderController@update'
                    ]);

                    Route::post('update-to-issued/{id}', [
                        'as' => 'admin.inventory.purchase-order.update-to-issued',
                        'uses' => 'PurchaseOrderController@updateToIssued'
                    ]);
                    
                    Route::post('update-to-cancelled/{id}', [
                        'as' => 'admin.inventory.purchase-order.update-to-cancelled',
                        'uses' => 'PurchaseOrderController@updateToCancelled'
                    ]);                    

                    Route::get('/products/search', [
                        'as' => 'admin.inventory.purchase-order.products-search',
                        'uses' => 'PurchaseOrderController@productsSearch'
                    ]);

                    Route::get('/warehouses/search', [
                        'as' => 'admin.inventory.purchase-order.warehouses-search',
                        'uses' => 'PurchaseOrderController@warehousesSearch'
                    ]);
                });

                // Transactions Routes
                Route::group(['prefix' => 'inventory', 'namespace' => 'Webkul\Admin\Http\Controllers\Inventory'], function () {

                    Route::group(['prefix' => 'transactions'], function () {
                        Route::get('/', [
                            'as' => 'admin.inventory.transactions.index',
                            'uses' => 'InventoryTransactionController@list'
                        ]);

                        Route::post('create', [
                            'as' => 'admin.inventory.transactions.store',
                            'uses' => 'InventoryTransactionController@create'
                        ]);

                        Route::get('search/product', [
                            'as' => 'admin.inventory.transactions.products-search',
                            'uses' => 'InventoryTransactionController@searchProduct'
                        ]);

                        Route::get('select/product/{product}', [
                            'as' => 'admin.inventory.transactions.select-product',
                            'uses' => 'InventoryTransactionController@selectProduct'
                        ]);

                        Route::get('show/product-sku/{sku}', [
                            'as' => 'admin.inventory.transactions.show-product-sku',
                            'uses' => 'InventoryTransactionController@showProductSku'
                        ]);
                        
                        Route::delete('delete-product/{id}', [
                            'as' => 'admin.inventory.transactions.delete-product',
                            'uses' => 'InventoryTransactionController@deleteProduct'
                        ]);                        

                        Route::get('profile/{id}', [
                            'as' => 'admin.inventory.transactions.show',
                            'uses' => 'InventoryTransactionController@profile'
                        ]);

                        Route::put('set-status/{InventoryTransaction}', [
                            'as' => 'admin.inventory.transactions.update-status',
                            'uses' => 'InventoryTransactionController@setStatus'
                        ]);
                    });

                    Route::group(['prefix' => 'adjustments'], function () {
                        Route::get('/', [
                            'as' => 'admin.inventory.adjustments.index',
                            'uses' => 'InventoryAdjustmentController@list'
                        ]);

                        Route::post('create', [
                            'as' => 'admin.inventory.adjustments.store',
                            'uses' => 'InventoryAdjustmentController@create'
                        ]);

                        Route::get('search/product', [
                            'as' => 'admin.inventory.adjustments.products-search',
                            'uses' => 'InventoryAdjustmentController@searchProduct'
                        ]);

                        Route::get('select/product/{product}', [
                            'as' => 'admin.inventory.adjustments.select-product',
                            'uses' => 'InventoryAdjustmentController@selectProduct'
                        ]);

                        Route::get('show/product-sku/{sku}', [
                            'as' => 'admin.inventory.adjustments.show-product-sku',
                            'uses' => 'InventoryAdjustmentController@showProductSku'
                        ]);
                        
                        Route::delete('delete-product/{id}', [
                            'as' => 'admin.inventory.adjustments.delete-product',
                            'uses' => 'InventoryAdjustmentController@deleteProduct'
                        ]);                          

                        Route::get('profile/{id}', [
                            'as' => 'admin.inventory.adjustments.show',
                            'uses' => 'InventoryAdjustmentController@profile'
                        ]);

                        Route::put('set-status/{InventoryAdjustment}', [
                            'as' => 'admin.inventory.adjustments.update-status',
                            'uses' => 'InventoryAdjustmentController@setStatus'
                        ]);
                    });
                });

                // Suppliers Routes
                Route::group(['prefix' => 'suppliers', 'namespace' => 'Webkul\Admin\Http\Controllers\Supplier'], function () {
                    Route::get('/', [
                        'as' => 'admin.inventory.suppliers.index',
                        'uses' => 'SupplierController@index'
                    ]);

                    Route::post('create', [
                        'as' => 'admin.inventory.suppliers.store',
                        'uses' => 'SupplierController@create'
                    ]);

                    Route::get('show/{id}', [
                        'as' => 'admin.inventory.suppliers.show',
                        'uses' => 'SupplierController@show'
                    ]);

                    Route::post('update/{id}', [
                        'as' => 'admin.inventory.suppliers.update',
                        'uses' => 'SupplierController@update'
                    ]);

                    Route::post('update-status/{id}', [
                        'as' => 'admin.inventory.suppliers.update-status',
                        'uses' => 'SupplierController@updateStatus'
                    ]);

                    Route::get('/get-products/{id}', [
                        'as' => 'admin.inventory.suppliers.get-products',
                        'uses' => 'SupplierController@getProducts'
                    ]);

                    Route::get('/get-product-skus', [
                        'as' => 'admin.inventory.suppliers.get-product-skus',
                        'uses' => 'SupplierController@getProductSkus'
                    ]);

                    Route::post('product/delete/{supplier_id}/{product_id}', [
                        'as' => 'admin.inventory.suppliers.product-delete',
                        'uses' => 'SupplierController@productDelete'
                    ]);

                    Route::post('delete/{id}', [
                        'as' => 'admin.inventory.suppliers.delete',
                        'uses' => 'SupplierController@delete'
                    ]);
                });

                // Shelve Routes
                Route::group(['prefix' => 'shelves', 'namespace' => 'Webkul\Admin\Http\Controllers\Shelve'], function () {

                    Route::get('/', [
                        'as' => 'admin.inventory.shelves.index',
                        'uses' => 'ShelveController@list'
                    ]);
                    Route::get('/show/{id}', [
                        'as' => 'admin.inventory.shelves.show',
                        'uses' => 'ShelveController@show'
                    ]);
                    Route::post('/create', [
                        'as' => 'admin.inventory.shelves.store',
                        'uses' => 'ShelveController@create'
                    ]);

                    Route::post('update/{id}', [
                        'as' => 'admin.inventory.shelves.update',
                        'uses' => 'ShelveController@update'
                    ]);

                    Route::post('delete/{id}', [
                        'as' => 'admin.inventory.shelves.delete',
                        'uses' => 'ShelveController@delete'
                    ]);
                });
                // ========================================================================             
                // *******************            
                // Accounting
                // *******************            
                // Accounting Routes
                Route::group(['prefix' => 'accounting', 'namespace' => 'Webkul\Admin\Http\Controllers\Accounting'], function () {

                    Route::get('driver-transactions', [
                        'as' => 'admin.accounting.driver-transactions.index',
                        'uses' => 'AccountingController@driverTransactions'
                    ]);

                    Route::get('driver-transaction-otp/{transaction_id}', [
                        'as' => 'admin.accounting.driver-transactions.otp',
                        'uses' => 'AccountingController@generateDriverTransactionOtp'
                    ]);

                    Route::get('area-manager-transactions', [
                        'as' => 'admin.accounting.area-manager-transactions.index',
                        'uses' => 'AccountingController@areaManagerTransactions'
                    ]);

                    Route::get('accountant-transactions', [
                        'as' => 'admin.accounting.accountant-transactions.index',
                        'uses' => 'AccountingController@accountantTransactions'
                    ]);

                    Route::get('area-manager-transaction-tickets/{transaction_id}', [
                        'as' => 'admin.accounting.area-manager-transactions.tickets',
                        'uses' => 'AccountingController@areaManagerTransactionTickets'
                    ]);

                    Route::post('area-manager-transaction-request', [
                        'as' => 'admin.accounting.area-manager-transactions.create',
                        'uses' => 'AccountingController@areaManagerTransactionRequest'
                    ]);

                    Route::put('area-manager-update-transaction-request/{transaction_id}', [
                        'as' => 'admin.accounting.area-manager-update-transactions.update',
                        'uses' => 'AccountingController@areaManagerUpdateTransactionRequest'
                    ]);

                    Route::put('accountant-update-transaction-request/{transaction_id}', [
                        'as' => 'admin.accounting.accountant-update-transactions.update',
                        'uses' => 'AccountingController@accountantUpdateTransactionRequest'
                    ]);
                });
                // ======================================================================== 
                // ===================
                // ==== Settings  ====  
                // ===================
                // *******************            
                Route::group(['prefix' => 'users', 'namespace' => 'Webkul\Admin\Http\Controllers\User'], function () {
                    Route::get('/', [
                        'as' => 'admin.settings.users.index',
                        'uses' => 'UserController@list'
                    ]);

                    Route::get('/{user}', [
                        'as' => 'admin.settings.users.show',
                        'uses' => 'UserController@show'
                    ]);
                    Route::post('', [
                        'as' => 'admin.settings.users.store',
                        'uses' => 'UserController@add'
                    ]);

                    Route::put('{user}', [
                        'as' => 'admin.settings.users.update',
                        'uses' => 'UserController@update'
                    ]);
                    Route::put('/set-status/{user}', [
                        'as' => 'admin.settings.users.update-status',
                        'uses' => 'UserController@setStatus'
                    ]);
                });

                // Roles Routes
                Route::group(['prefix' => 'roles', 'namespace' => 'Webkul\Admin\Http\Controllers\Role'], function () {

                    Route::get('/fetch-permissions-data', [
                        'as' => 'admin.settings.roles.fetch-permission-data',
                        'uses' => 'RoleController@fetchPermissionsData'
                    ]);

                    Route::get('/', [
                        'as' => 'admin.settings.roles.index',
                        'uses' => 'RoleController@index'
                    ]);

                    Route::get('/{role}', [
                        'as' => 'admin.settings.roles.show',
                        'uses' => 'RoleController@show'
                    ]);
                    Route::post('', [
                        'as' => 'admin.settings.roles.store',
                        'uses' => 'RoleController@create'
                    ]);

                    Route::put('{id}', [
                        'as' => 'admin.settings.roles.update',
                        'uses' => 'RoleController@update'
                    ]);
                    Route::put('/update-status/{role}', [
                        'as' => 'admin.settings.roles.update-status',
                        'uses' => 'RoleController@updateStatus'
                    ]);

                    Route::delete('{role}', [
                        'as' => 'admin.settings.roles.delete',
                        'uses' => 'RoleController@delete'
                    ]);
                });
                // ========================================================================           
                // 
                // Log Routes
                Route::group(['prefix' => 'logs', 'namespace' => 'Webkul\Admin\Http\Controllers\ActivityLog'], function () {

                    Route::get('/', [
                        'as' => 'admin.settings.logs.index',
                        'uses' => 'ActivityLogController@list'
                    ]);
                    Route::get('/show/{id}', [
                        'as' => 'admin.settings.logs.show',
                        'uses' => 'ActivityLogController@show'
                    ]);
                });
                
                // Banner Routes
                Route::group(['prefix' => 'areas', 'namespace' => 'Webkul\Admin\Http\Controllers\Area'], function () {
                    Route::get('/', [// {sale} or {deal}
                        'as' => 'admin.settings.areas.index',
                        'uses' => 'AreaController@list'
                    ]);
                    Route::get('/show/{area}', [
                        'as' => 'admin.settings.areas.show',
                        'uses' => 'AreaController@show'
                    ]);
//                    Route::post('/', [
//                        'as' => 'admin.settings.areas.store',
//                        'uses' => 'AreaController@add'
//                    ]);

                    Route::put('/{area}', [
                        'as' => 'admin.settings.areas.update',
                        'uses' => 'AreaController@update'
                    ]);

//                    Route::delete('/{area}', [
//                        'as' => 'admin.settings.areas.delete',
//                        'uses' => 'AreaController@delete'
//                    ]);
//
//                    Route::put('/set-status/{area}', [// {sale} or {deal}
//                        'as' => 'admin.settings.areas.update-status',
//                        'uses' => 'AreaController@setStatus'
//                    ]);
//
//                    Route::put('/set-default/{area}', [// {sale} or {deal}
//                        'as' => 'admin.settings.areas.update-default',
//                        'uses' => 'AreaController@setDefault'
//                    ]);
 
                });
                
                
                // ========================================================================           
                // 
                // reports Routes
                Route::group(['prefix' => 'reports', 'namespace' => 'Webkul\Admin\Http\Controllers\Report'], function () {

                    Route::get('/list-reports', [
                        'as' => 'admin.reports.listReports',
                        'uses' => 'ReportController@listReports'
                    ]);
                    Route::get('/export/', [
                        'as' => 'admin.reports.export',
                        'uses' => 'ReportController@export'
                    ]);
                });                

                // *******************            
                // Global Routes (Backend)
                // *******************  
                // user module
                Route::get('/logout', 'Webkul\User\Http\Controllers\Api\AuthController@logout')->name('admin.session.destroy');
                Route::get('/auth/me', 'Webkul\User\Http\Controllers\Api\AuthController@me')->name('admin.users.me');

                // Core Routes
                Route::group(['prefix' => 'core', 'namespace' => 'Webkul\Admin\Http\Controllers\Core'], function () {
                    Route::get('channels/', 'CoreController@channelList')->name('admin.core.channel.list');
                    Route::get('areas/', 'CoreController@areaList')->name('admin.core.area.list');
                });

                // Common Routes
                Route::get('fetchAll/{type}', 'Webkul\Admin\Http\Controllers\CommonController@fetchAll')->name('admin.core.fetchAll');
                Route::get('hashTags', 'Webkul\Admin\Http\Controllers\CommonController@hashTags')->name('admin.core.hashTags');
                Route::get('getAll/{type}', 'Webkul\Admin\Http\Controllers\CommonController@getAll')->name('admin.core.getAll');
                Route::get('/fetch-roles', 'Webkul\Admin\Http\Controllers\Role\RoleController@fetchRoles')->name('admin.roles.fetchRoles');
                Route::get('/get-areas-warehouses', 'Webkul\Admin\Http\Controllers\CommonController@getAreasWithWarehouses')->name('admin.areas.getAreasWithWarehouses');

                // Permissions Routes
                Route::group(['prefix' => 'permissions', 'namespace' => 'Webkul\Admin\Http\Controllers\Permission'], function () {
                    Route::get('/clear', [
                        'as' => 'admin.permissions.clear',
                        'uses' => 'PermissionController@clear'
                    ]);
                    Route::get('/build', [
                        'as' => 'admin.permissions.build',
                        'uses' => 'PermissionController@build'
                    ]);
                    Route::get('/translate', [
                        'as' => 'admin.permissions.translate',
                        'uses' => 'PermissionController@translate'
                    ]);
                });
                // ======================================================================== 
                // Permissions Routes
                Route::group(['prefix' => 'queues', 'namespace' => 'Webkul\Admin\Http\Controllers\Queue'], function () {
                    Route::get('/generate/', [
                        'as' => 'admin.queues.generate',
                        'uses' => 'QueueController@generate'
                    ]);
                });
                // ========================================================================         
            }
    );
});

