<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/produtos', [ProductController::class, 'index'])
    ->name('products.index');

Route::get('/produtos/{product:slug}', [ProductController::class, 'show'])
    ->name('products.show');

Route::get('/carrinho', [CartController::class, 'index'])
    ->name('cart.index');

Route::post('/carrinho', [CartController::class, 'store'])
    ->name('cart.store');

Route::patch('/carrinho/{variant}', [CartController::class, 'update'])
    ->name('cart.update');

Route::delete('/carrinho/{variant}', [CartController::class, 'destroy'])
    ->name('cart.destroy');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
