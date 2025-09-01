<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('documents', App\Http\Controllers\DocumentController::class)->only([
        'index', 'create', 'store', 'show', 'update',
    ]);
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
