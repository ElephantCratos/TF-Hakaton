<?php

use Illuminate\Support\Facades\Route;
use Modules\Course\Http\Controllers\CourseController;

Route::prefix('cources')->group(function () {

    Route::post('create', [CourseController::class, 'create']);
    Route::get('list', [CourseController::class, 'list']);
    Route::post('{id}', [CourseController::class, 'update']);
    Route::delete('{id}/soft', [CourseController::class, 'soft']);
    Route::delete('{id}/hard', [CourseController::class, 'hard']);

   // Route::middleware(['auth:sanctum'])->group(function () {
   //    
   // });
});

