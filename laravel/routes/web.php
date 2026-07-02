<?php

use App\Http\Controllers\Admin\DocumentDownloadController;
use App\Http\Controllers\Admin\DocumentViewController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

// Document download & view
Route::get('admin/documents/{document}/download', [DocumentDownloadController::class, 'download'])
    ->name('admin.documents.download')
    ->middleware(['web', 'auth']);

Route::get('admin/documents/{document}/view', [DocumentViewController::class, 'view'])
    ->name('admin.documents.view')
    ->middleware(['web', 'auth']);
