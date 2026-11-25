<?php

use App\Http\Controllers\Shop\AccountController;
use App\Http\Controllers\Shop\CartController;
use App\Http\Controllers\Shop\HomeController;
use App\Http\Controllers\Shop\ProductController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Loja (Blade)
|--------------------------------------------------------------------------
*/

Route::get('/', [HomeController::class, 'index'])
    ->name('home');

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

Route::delete('/carrinho', [CartController::class, 'clear'])
    ->name('cart.clear');

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/minha-conta', [AccountController::class, 'index'])
        ->name('account.index');

    Route::patch('/minha-conta/perfil', [AccountController::class, 'updateProfile'])
        ->name('account.profile.update');

    Route::put('/minha-conta/senha', [AccountController::class, 'updatePassword'])
        ->name('account.password.update');
});

require __DIR__.'/admin.php';
require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
