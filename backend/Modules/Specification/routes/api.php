<?php

use Illuminate\Support\Facades\Route;
use Modules\Specification\Http\Controllers\SpecificationController;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::apiResource('specifications', SpecificationController::class)->names('specification');
});
