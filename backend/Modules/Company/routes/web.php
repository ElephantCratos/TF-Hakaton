<?php

use Illuminate\Support\Facades\Route;
use Modules\Company\Http\Controllers\CompanyController;

Route::prefix('v1')->group(function () {
    Route::apiResource('companies', CompanyController::class)->names('company');
});
