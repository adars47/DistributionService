<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::post('/verify', 'App\Http\Controllers\SubmitController@verify');
Route::get('/publicKey', 'App\Http\Controllers\SubmitController@publicKey');
Route::post('/uploadFiles/{uploadId}', 'App\Http\Controllers\SubmitController@uploadFiles');
Route::post('/submitRules', 'App\Http\Controllers\SubmitController@submitRules');
Route::get('/getRules/{uploadId}', 'App\Http\Controllers\VerificationController@getRules');
Route::post('/satisfy/{ruleId}', 'App\Http\Controllers\VerificationController@satisfy');
Route::get('/release/{uploadId}', 'App\Http\Controllers\VerificationController@release');

