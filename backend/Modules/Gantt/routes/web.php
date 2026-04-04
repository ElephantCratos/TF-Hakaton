<?php

use Illuminate\Support\Facades\Route;
use Modules\Gantt\Http\Controllers\GanttController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('gantts', GanttController::class)->names('gantt');
});
