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

Route::get('/', function () {
    return redirect('login');
});

Route::get('/login', 'LaravelSQRLAuthExemples\ExempleController@getAuthPage')->name('login');
Route::post('/login', 'LaravelSQRLAuthExemples\ExempleController@login');
Route::post('/logout', 'LaravelSQRLAuthExemples\ExempleController@logout')->name('logout');

Route::get('/dashboard', 'LaravelSQRLAuthExemples\ExempleController@getDashboardPage')->name('dashboard');

Route::post('/transfer', 'LaravelSQRLAuthExemples\ExempleController@getTransferConfirmation');

Route::get('/resetpw', 'LaravelSQRLAuthExemples\ExempleController@getResetPWPage')->name('resetpw');
Route::post('/resetpw', 'LaravelSQRLAuthExemples\ExempleController@resetPW');

Route::post('/newlogin', 'LaravelSQRLAuthExemples\ExempleController@newlogin');
Route::post('/newaccount', 'LaravelSQRLAuthExemples\ExempleController@newAcc');
