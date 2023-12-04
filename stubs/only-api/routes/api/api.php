<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\V1\AuthenticationController;

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

Route::controller(AuthenticationController::class)->group(function () {
    Route::post('register', 'register')->name('register');
    Route::post('login', 'login')->name('login');
    Route::post('password/forgot', 'sendResetLinkEmail')->name('password.forgot')
        ->middleware('throttle:5,1');
    Route::post('password/reset/email', 'resetPasswordByEmail')->name('password.update.email');
    Route::post('password/reset/contact', 'resetPasswordByContactNumber')->name('password.update.contact');
    Route::post('logout', 'logout')->name('logout')->middleware('auth:sanctum');
});
