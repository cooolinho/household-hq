<?php

use App\Filament\Admin\Pages\Features\MergeImagesToPdfWizardProgressPage;
use App\Http\Controllers\Admin\DocumentDownloadController;
use App\Http\Controllers\Admin\DocumentViewController;
use App\Http\Controllers\Admin\TempImagePreviewController;
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

// Temporäre Bild-Vorschau für Scan-Wizard (nur eigene Bilder des eingeloggten Users)
Route::get('admin/scan-temp-preview/{filename}', [TempImagePreviewController::class, 'preview'])
    ->name('admin.scan-temp-preview')
    ->middleware(['web', 'auth']);

// Merge Images to PDF Wizard Progress Page
Route::get('/' . MergeImagesToPdfWizardProgressPage::SLUG . '/{processingCacheKey}', MergeImagesToPdfWizardProgressPage::class)
    ->name(MergeImagesToPdfWizardProgressPage::ROUTE_NAME)
    ->middleware(['web', 'auth']);
