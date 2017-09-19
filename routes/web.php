<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Auth::routes();

Route::group(["middleware" => "auth"], function(){
    Route::get('/', 'HomeController@index')->name('home');
    Route::get('/home', 'HomeController@transfer');
    Route::post('/get-customers', 'HomeController@getCustomers');
    Route::post('/send-customers', 'HomeController@sendCustomers');
});