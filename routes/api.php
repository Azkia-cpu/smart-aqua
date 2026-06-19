<?php

use App\Http\Controllers\Api\PumpControlApiController;
use App\Http\Controllers\Api\SensorDataController;
use App\Http\Middleware\ValidateDeviceToken;
use Illuminate\Support\Facades\Route;

Route::middleware(ValidateDeviceToken::class)->group(function () {
    Route::post('/sensor-data', [SensorDataController::class, 'store']);
    Route::get('/pump-status/{pondCode}', [PumpControlApiController::class, 'status']);
});
