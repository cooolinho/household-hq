<?php

use App\Http\Controllers\Admin\DocumentDownloadController;
use App\Http\Controllers\Admin\DocumentViewController;
use App\Http\Controllers\Admin\GoalImageController;
use App\Http\Controllers\Admin\InventoryPreviewImageController;
use App\Http\Controllers\Admin\TempImagePreviewController;
use App\Support\PanelRouter;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect(PanelRouter::urlFor(Auth::user()));
});

// Zentraler Login: das Admin-Panel hat keine eigene Login-Seite, sondern
// leitet (wie /horizon) hierher weiter. Diese Route MUSS "login" heißen -
// Filament\Http\Middleware\Authenticate::redirectTo() fällt für Panels ohne
// eigene Login-Route auf route('login') zurück.
Route::get('/login', function () {
    return redirect(Filament::getPanel('app')->getLoginUrl());
})->name('login');

// Document download & view
Route::get('app/documents/{document}/download', [DocumentDownloadController::class, 'download'])
    ->name('app.documents.download')
    ->middleware(['web', 'auth']);

Route::get('app/documents/{document}/view', [DocumentViewController::class, 'view'])
    ->name('app.documents.view')
    ->middleware(['web', 'auth']);

// Temporäre Bild-Vorschau für Scan-Wizard (nur eigene Bilder des eingeloggten Users)
Route::get('app/scan-temp-preview/{filename}', [TempImagePreviewController::class, 'preview'])
    ->name('app.scan-temp-preview')
    ->middleware(['web', 'auth']);

Route::get('app/inventory-preview/{type}/{record}', [InventoryPreviewImageController::class, 'show'])
    ->name('app.inventory.preview')
    ->middleware(['web', 'auth']);

Route::get('app/goals/{goal}/image', [GoalImageController::class, 'show'])
    ->name('app.goals.image')
    ->middleware(['web', 'auth']);
