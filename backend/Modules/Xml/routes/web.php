<?php

use Illuminate\Support\Facades\Route;
use Modules\Xml\Http\Controllers\XmlController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('xmls', XmlController::class)->names('xml');
});
