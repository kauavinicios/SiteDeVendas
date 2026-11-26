<?php

use App\Enums\Permission;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\CampaignController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductImageController;
use App\Http\Controllers\Admin\ProductVariantController;
use App\Http\Controllers\Admin\StockController;
use App\Http\Controllers\Admin\UserController;
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

            // /admin/usuarios — gestão da equipe (exclusivo do Administrador; sem exclusão)
            Route::middleware('can:'.Permission::ManageUsers->value)->group(function () {
                Route::get('/usuarios', [UserController::class, 'index'])
                    ->name('users.index');

                Route::post('/usuarios', [UserController::class, 'store'])
                    ->name('users.store');

                Route::put('/usuarios/{user}', [UserController::class, 'update'])
                    ->name('users.update');

                Route::patch('/usuarios/{user}/status', [UserController::class, 'toggleStatus'])
                    ->name('users.toggle-status');
            });

            // /admin/clientes — consulta de clientes e inscritos em novidades (sem exclusão)
            Route::get('/clientes', [CustomerController::class, 'index'])
                ->middleware('can:'.Permission::ViewCustomers->value)
                ->name('customers.index');

            Route::patch('/clientes/{user}/status', [CustomerController::class, 'toggleStatus'])
                ->middleware('can:'.Permission::ManageUsers->value)
                ->name('customers.toggle-status');

            // /admin/novidades — campanhas de marketing por e-mail (LGPD)
            Route::middleware('can:'.Permission::ManageCampaigns->value)->group(function () {
                Route::get('/novidades', [CampaignController::class, 'index'])
                    ->name('campaigns.index');

                Route::post('/novidades', [CampaignController::class, 'store'])
                    ->name('campaigns.store');

                Route::put('/novidades/{campaign}', [CampaignController::class, 'update'])
                    ->name('campaigns.update');

                Route::get('/novidades/{campaign}/previa', [CampaignController::class, 'preview'])
                    ->name('campaigns.preview');

                Route::post('/novidades/{campaign}/enviar', [CampaignController::class, 'send'])
                    ->name('campaigns.send');
            });

            // /admin/pedidos — acompanhamento e status dos pedidos (sem exclusão)
            Route::get('/pedidos', [OrderController::class, 'index'])
                ->middleware('can:'.Permission::ViewOrders->value)
                ->name('orders.index');

            Route::patch('/pedidos/{order}/status', [OrderController::class, 'updateStatus'])
                ->middleware('can:'.Permission::ManageOrders->value)
                ->name('orders.update-status');

            // /admin/relatorios — estatísticas de vendas, demanda e estimativa de lucros
            Route::get('/relatorios', [AnalyticsController::class, 'index'])
                ->middleware('can:'.Permission::ViewFinancialReports->value)
                ->name('analytics.index');
        });
    });
