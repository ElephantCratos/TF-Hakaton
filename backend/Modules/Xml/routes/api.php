<?php

use Illuminate\Support\Facades\Route;
use Modules\Xml\Http\Controllers\XmlController;
use Modules\Xml\Http\Controllers\XmlImportController;

Route::prefix('xml')->name('xml.')->group(function () {
 
    // Загрузка и запуск импорта
    Route::post('/import', [XmlImportController::class, 'import'])->name('import');
 
    // История батчей импорта
    Route::get('/batches', [XmlImportController::class, 'batches'])->name('batches.index');
    Route::get('/batches/{batch}', [XmlImportController::class, 'batchShow'])->name('batches.show');
 
    // Логи конкретного батча
    Route::get('/batches/{batch}/logs', [XmlImportController::class, 'batchLogs'])->name('batches.logs');
});
 