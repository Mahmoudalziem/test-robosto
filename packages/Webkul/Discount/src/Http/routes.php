<?php

Route::group(['middleware' => ['web', 'admin']], function () {

    Route::get('/admin/discount', 'Webkul\Discount\Http\Controllers\Admin\DiscountController@index')->defaults('_config', [
        'view' => 'discount::admin.index',
    ])->name('discount.admin.index');

});