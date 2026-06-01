<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\IotGpsTrackController;

Route::post('/iot/gps-track', [IotGpsTrackController::class, 'store'])
    ->name('api.iot.gps-track');