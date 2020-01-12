<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(['namespace'=>'\DestruidorPT\LaravelSQRLAuth\App\Http\Controllers'], function() {
    Route::get('/sqrl', 'SQRL\SQRLControllerAPI@checkIfisReady');
    Route::post('/sqrl', 'SQRL\SQRLControllerAPI@sqrl');
});
