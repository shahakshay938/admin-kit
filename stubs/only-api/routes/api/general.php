<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Common\V1\GeneralController;

/*
|--------------------------------------------------------------------------
| API Common Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API common routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::controller(GeneralController::class)->group(function () {
    Route::post('generate-checksum', 'generateChecksum')->name('generate-checksum');
    Route::post('app-status', 'appStatus');
});
