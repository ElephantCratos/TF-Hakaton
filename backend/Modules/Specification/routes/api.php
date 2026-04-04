<?php

use Illuminate\Support\Facades\Route;
use Modules\Specification\Http\Controllers\SpecificationController;

/*
|--------------------------------------------------------------------------
| Specification API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    // --- Спецификации ---
    Route::get('specifications', [SpecificationController::class, 'index']);
    Route::post('specifications', [SpecificationController::class, 'store']);
    Route::get('specifications/{specification}', [SpecificationController::class, 'show']);
    Route::put('specifications/{specification}', [SpecificationController::class, 'update']);
    Route::delete('specifications/{specification}', [SpecificationController::class, 'destroy']);

    // --- Привязка / открепление групп ---
    Route::post('specifications/{specification}/groups/{training_group}', [SpecificationController::class, 'attachGroup']);
    Route::delete('specifications/{specification}/groups/{training_group}', [SpecificationController::class, 'detachGroup']);
});
