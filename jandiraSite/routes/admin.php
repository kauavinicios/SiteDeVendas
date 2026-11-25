<?php

use App\Enums\Permission;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductImageController;
use App\Http\Controllers\Admin\ProductVariantController;
use App\Http\Controllers\Admin\StockController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Administração
|--------------------------------------------------------------------------
|
| Rotas do painel (React / Inertia). Carregado pelo routes/web.php, portanto
| herda o middleware "web".
|
*/

Route::middleware(['auth', 'active', 'staff'])
    ->prefix('admin')
    ->group(function () {

        // Mantém o nome "dashboard" (usado pelo starter kit e pelo header da loja)
        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->middleware('can:'.Permission::AccessAdmin->value)
            ->name('dashboard');

        Route::name('admin.')->group(function () {

            // /admin/produtos — criação/edição protegida por ManageProducts e listagem por ViewProducts
            // IMPORTANTE: "create" precisa vir antes de "{product}" ou usar resource separado
            Route::middleware('can:'.Permission::ManageProducts->value)->group(function () {
                Route::resource('produtos', ProductController::class)
                    ->only(['create', 'store', 'edit', 'update'])
                    ->parameters(['produtos' => 'product'])
                    ->names('products');

                // /admin/produtos/{product}/variacoes — store, update (desativação pelo campo active)
                // scoped(): a variação precisa pertencer ao produto da URL (senão 404)
                Route::resource('produtos.variacoes', ProductVariantController::class)
                    ->only(['store', 'update'])
                    ->parameters(['produtos' => 'product', 'variacoes' => 'variant'])
                    ->names('product-variants')
                    ->scoped();

                // /admin/produtos/{product}/imagens — upload, reordenação e exclusão
                Route::post('/produtos/{product}/imagens', [ProductImageController::class, 'store'])
                    ->name('product-images.store');

                Route::patch('/produtos/{product}/imagens/reordenar', [ProductImageController::class, 'reorder'])
                    ->name('product-images.reorder');

                Route::delete('/produtos/{product}/imagens/{image}', [ProductImageController::class, 'destroy'])
                    ->name('product-images.destroy')
                    ->scopeBindings();
            });

            Route::get('/produtos', [ProductController::class, 'index'])
                ->middleware('can:'.Permission::ViewProducts->value)
                ->name('products.index');

            // /admin/categorias
            Route::get('/categorias', [CategoryController::class, 'index'])
                ->middleware('can:'.Permission::ViewCategories->value)
                ->name('categories.index');

            Route::resource('categorias', CategoryController::class)
                ->only(['store', 'update'])
                ->parameters(['categorias' => 'category'])
                ->names('categories')
                ->middleware('can:'.Permission::ManageCategories->value);

            Route::delete('/categorias/{category}', [CategoryController::class, 'destroy'])
                ->middleware('can:'.Permission::DeleteCategories->value)
                ->name('categories.destroy');

            // /admin/estoque — listagem e atualização rápida de estoque
            Route::get('/estoque', [StockController::class, 'index'])
                ->middleware('can:'.Permission::ViewStock->value)
                ->name('stock.index');

            Route::patch('/estoque/{variant}', [StockController::class, 'update'])
                ->middleware('can:'.Permission::ManageStock->value)
                ->name('stock.update');
        });
    });
