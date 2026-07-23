<?php

/*
|--------------------------------------------------------------------------
| Dynamic Footer routes
|--------------------------------------------------------------------------
| Add this line to the bottom of routes/web.php:
|
|     require __DIR__ . '/footer.php';
|
| Adjust the prefix and middleware below to match your own admin area.
*/

use App\Http\Controllers\Admin\FooterItemController;
use App\Http\Controllers\Admin\FooterPanelController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth'])   // 'web' is NOT listed here: this file is required into routes/web.php,
                             // which already applies the 'web' group via RouteServiceProvider.
                             // Add your own admin middleware here too, e.g. 'can:manage-footer'.
    ->group(function () {

        // Footer manager screen
        Route::get('footer', [FooterPanelController::class, 'index'])->name('footer.index');

        // Panels (the footer columns)
        Route::post('footer/panels', [FooterPanelController::class, 'store'])->name('footer.panels.store');
        Route::put('footer/panels/{panel}', [FooterPanelController::class, 'update'])->name('footer.panels.update');
        Route::delete('footer/panels/{panel}', [FooterPanelController::class, 'destroy'])->name('footer.panels.destroy');
        Route::post('footer/panels/reorder', [FooterPanelController::class, 'reorder'])->name('footer.panels.reorder');

        // Items inside the panels (links / headings / text / images)
        Route::post('footer/items', [FooterItemController::class, 'store'])->name('footer.items.store');
        Route::put('footer/items/{item}', [FooterItemController::class, 'update'])->name('footer.items.update');
        Route::delete('footer/items/{item}', [FooterItemController::class, 'destroy'])->name('footer.items.destroy');
        Route::post('footer/items/reorder', [FooterItemController::class, 'reorder'])->name('footer.items.reorder');
    });
