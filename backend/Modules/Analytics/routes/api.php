<?php

use Illuminate\Support\Facades\Route;
use Modules\Analytics\Http\Controllers\AnalyticsController;

Route::middleware('auth:sanctum')->prefix('analytics')->group(function () {

    // GET /api/analytics/companies — сводная аналитика по всем компаниям
    Route::get('companies', [AnalyticsController::class, 'companies']);

    // GET /api/analytics/companies/{id} — детализация по компании
    Route::get('companies/{id}', [AnalyticsController::class, 'companyDetail']);
});
