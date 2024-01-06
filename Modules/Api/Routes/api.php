<?php

use Modules\Api\Http\Controllers\AuthController;
use Modules\Api\Http\Controllers\KycController;
use Modules\Api\Http\Controllers\PostController;
use Modules\Api\Http\Controllers\UserController;

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

Route::prefix('/')->group(function () {
    Route::controller(AuthController::class)->group(function () {
        Route::post('/login', 'login');
    });
});

Route::middleware('auth:api')->group(function () {
    Route::prefix('/')->group(function () {
        Route::controller(KycController::class)->group(function () {
            Route::post('/tobank/{id}', 'pay');
            Route::post('/identity/{id}', 'data');
            Route::get('/check/{id}', 'result');
            Route::post('/uploadPhoto/{id}', 'uploadPhoto');
            Route::post('/{id}/video', 'websdk');


            Route::post('/changePhoto/{id}', 'changePhoto');
            Route::post('/uploadPhotoSkip/{id}', 'uploadPhotoSkip');
        });


    });
});

Route::prefix('/')->group(function () {
    Route::controller(KycController::class)->group(function () {
        Route::get('/banback', 'banback');
    });
    Route::controller(PostController::class)->group(function () {
        Route::get('/post/{ref}', 'post');
    });

    Route::controller(AuthController::class)->group(function () {
        Route::post('/todivar', 'auth');
        Route::get('/fromdivar', 'authback');
        Route::get('/auth-token/{id}', 'authToken');
    });
    Route::controller(UserController::class)->group(function () {
        Route::get('/user-data/{id}', 'userData');
    });

});
