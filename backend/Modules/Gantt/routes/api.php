<?php

use Illuminate\Support\Facades\Route;
use Modules\Gantt\Http\Controllers\GanttController;

Route::middleware('auth:sanctum')->prefix('gantt')->name('gantt.')->group(function () {

    // GET /api/gantt?from=2025-01-01&to=2025-12-31&status=active&course_id=1
    Route::get('/', [GanttController::class, 'index'])->name('index');

    // GET /api/gantt/export?from=2025-01-01&to=2025-12-31&format=csv
    Route::get('/export', [GanttController::class, 'export'])->name('export');

    // PATCH /api/gantt/{trainingGroup}/dates
    Route::patch('/{trainingGroup}/dates', [GanttController::class, 'updateDates'])->name('dates');

    // PATCH /api/gantt/{trainingGroup}/color
    Route::patch('/{trainingGroup}/color', [GanttController::class, 'updateColor'])->name('color');
});