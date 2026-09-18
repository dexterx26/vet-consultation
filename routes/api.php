<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\ApiController;

Route::prefix('v1')->group(function () {
    Route::post('/login', [ApiController::class, 'login']);
    Route::get('/vets', [ApiController::class, 'getVets']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/pets', [ApiController::class, 'getPets']);
        Route::get('/consultations', [ApiController::class, 'getConsultations']);
    });
});
