<?php

use Illuminate\Support\Facades\Route;
use Modules\Specification\Http\Controllers\SpecificationController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('specifications', SpecificationController::class)->names('specification');
});
