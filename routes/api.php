<?php

declare(strict_types=1);

use App\Http\Controllers\ApiAuthController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [ApiAuthController::class, 'logout']);
});

Route::post('/login', [ApiAuthController::class, 'login']);
