<?php

use App\Http\Controllers\Dev\MockInfinitePayController;
use App\Http\Controllers\Shop\AccountController;
use App\Http\Controllers\Shop\CartController;
use App\Http\Controllers\Shop\CheckoutReturnController;
use App\Http\Controllers\Shop\ContactController;
use App\Http\Controllers\Shop\FavoriteController;
use App\Http\Controllers\Shop\HomeController;
use App\Http\Controllers\Shop\LegalController;
use App\Http\Controllers\Shop\NewsletterController;
use App\Http\Controllers\Shop\OrderController;
use App\Http\Controllers\Shop\PageController;
use App\Http\Controllers\Shop\ProductController;
use App\Http\Controllers\Shop\StockAlertController;
use App\Http\Controllers\Webhooks\InfinitePayWebhookController;
use App\Models\ContactMessage;
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

Route::get('/sobre', [PageController::class, 'about'])
    ->name('pages.about');

Route::get('/contato', [ContactController::class, 'index'])
    ->name('contact.index');

Route::post('/contato', [ContactController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('contact.store');

Route::get('/termos-de-uso', [LegalController::class, 'terms'])
    ->name('legal.terms');

Route::get('/politica-de-privacidade', [LegalController::class, 'privacy'])
    ->name('legal.privacy');

Route::get('/novidades/cancelar/{user}', [NewsletterController::class, 'unsubscribe'])
    ->middleware('signed')
    ->name('newsletter.unsubscribe');

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

Route::post('/pedidos', [OrderController::class, 'store'])
    ->middleware('throttle:15,1')
    ->name('orders.store');

Route::get('/checkout/retorno', CheckoutReturnController::class)
    ->name('checkout.return');

Route::post('/webhooks/infinitepay/{secret}', InfinitePayWebhookController::class)
    ->name('webhooks.infinitepay');

Route::post('/alertas-estoque', [StockAlertController::class, 'store'])
    ->middleware('throttle:15,1')
    ->name('stock-alerts.store');

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/minha-conta', [AccountController::class, 'index'])
        ->name('account.index');

    Route::patch('/minha-conta/perfil', [AccountController::class, 'updateProfile'])
        ->name('account.profile.update');

    Route::put('/minha-conta/senha', [AccountController::class, 'updatePassword'])
        ->name('account.password.update');

    Route::delete('/minha-conta', [AccountController::class, 'destroy'])
        ->name('account.destroy');

    Route::post('/favoritos/{product}', [FavoriteController::class, 'toggle'])
        ->name('favorites.toggle');
});

if (app()->isLocal()) {
    Route::prefix('dev/emails')->group(function () {
        Route::get('/reset-password', function () {
            $url = url('/reset-password/token-exemplo?email=cliente@gmail.com');

            return view('emails.reset-password', [
                'userName' => 'Maria Silva',
                'resetUrl' => $url,
                'actionText' => 'Redefinir minha senha',
                'actionUrl' => $url,
                'expireMinutes' => 60,
            ]);
        });

        Route::get('/verify-email', function () {
            $url = url('/verify-email/1/hash-exemplo');

            return view('emails.verify-email', [
                'userName' => 'Maria Silva',
                'verificationUrl' => $url,
                'actionText' => 'Confirmar meu e-mail',
                'actionUrl' => $url,
                'expireMinutes' => 60,
            ]);
        });

        Route::get('/contact-message', function () {
            $sample = new ContactMessage([
                'name' => 'Maria Silva',
                'email' => 'maria@exemplo.com',
                'phone' => '(67) 99999-9999',
                'subject' => 'Dúvida sobre jogo de cama casal',
                'message' => "Olá! Gostaria de saber se o jogo de cama em algodão 200 fios está disponível na cor rosê para entrega em Campo Grande.",
            ]);
            $sample->created_at = now();

            return view('emails.contact-message', [
                'contactMessage' => $sample,
                'actionText' => 'Abrir caixa de mensagens',
                'actionUrl' => url('/admin/mensagens'),
            ]);
        });
    });
}

if (app()->environment(['local', 'testing'])) {
    Route::prefix('dev/infinitepay')->name('dev.infinitepay.')->group(function () {
        Route::get('/checkout/{slug}', [MockInfinitePayController::class, 'show'])
            ->name('checkout');

        Route::post('/checkout/{slug}/simulate', [MockInfinitePayController::class, 'simulate'])
            ->name('simulate');

        Route::get('/recibo/{slug}', [MockInfinitePayController::class, 'receipt'])
            ->name('receipt');
    });
}

require __DIR__.'/admin.php';
require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
