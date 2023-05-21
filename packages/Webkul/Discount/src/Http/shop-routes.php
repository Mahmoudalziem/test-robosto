<?php

Route::group(['middleware' => ['web', 'theme', 'locale', 'currency']], function () {

    Route::get('/discount', 'Webkul\Discount\Http\Controllers\Shop\DiscountController@index')->defaults('_config', [
        'view' => 'discount::shop.index',
    ])->name('discount.shop.index');

});