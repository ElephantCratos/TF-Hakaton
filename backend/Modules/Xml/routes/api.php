<?php

use Illuminate\Support\Facades\Route;
use Modules\Xml\Http\Controllers\XmlController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('xmls', XmlController::class)->names('xml');
});
