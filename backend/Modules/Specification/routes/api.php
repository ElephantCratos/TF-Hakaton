<?php

use Illuminate\Support\Facades\Route;
use Modules\Specification\Http\Controllers\SpecificationController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('specifications', SpecificationController::class)->names('specification');
});
