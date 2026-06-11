<?php

use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', HomeController::class)->name('home');

Route::get('/admin/{path?}', function (?string $path = null) {
    $base = trim(config('freedom.admin_path'), '/');
    $target = $path ? "{$base}/{$path}" : $base;

    return redirect('/'.$target, 301);
})->where('path', '.*');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
