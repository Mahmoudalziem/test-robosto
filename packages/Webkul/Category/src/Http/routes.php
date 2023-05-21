<?php

// Category Routes
Route::group(['prefix' => 'api/categories', 'middleware' => ['api']], function () {

    // Customer Auth Routes
    Route::group(['namespace' => 'Webkul\Category\Http\Controllers'], function () {

        Route::get('/', [
            'as'    =>  'categories.index',
            'uses'  =>  'CategoryController@index'
        ]);
        
        Route::get('/popular', [
            'as'    =>  'categories.popular',
            'uses'  =>  'CategoryController@popular'
        ]);

        Route::get('show/{id}', [
            'as'    =>  'categories.show',
            'uses'  =>  'CategoryController@show'
        ]);

        Route::get('sub-categories/popular', [
            'as'    =>  'sub-categories.popular',
            'uses'  =>  'SubCategoryController@popular'
        ]);
        
        Route::get('sub-categories/show/{id}', [
            'as'    =>  'sub-categories.show',
            'uses'  =>  'SubCategoryController@show'
        ]);

        // get all subcategories that has products in selected area
        Route::get('sub-categories/area', [
            'as'    =>  'sub-categories.area',
            'uses'  =>  'SubCategoryController@byAreaId'
        ]);


        // get all products that has stock in selected area by sub_category_id
        Route::get('sub-categories/{id}/products', [
            'as'    =>  'sub-categories.products.by-subcategory-id',
            'uses'  =>  'SubCategoryController@ProductsbySubcategoryId'
        ]);
    });
});

// Sub Category Routes
Route::group(['prefix' => 'api/sub-categories', 'middleware' => ['api']], function () {

    // Customer Auth Routes
    Route::group(['namespace' => 'Webkul\Category\Http\Controllers'], function () {

        Route::get('/{id}/products', [
            'as'    =>  'sub-categories.categories.index',
            'uses'  =>  'SubCategoryController@getProducts'
        ]);


    });
});




