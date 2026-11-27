<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StockUpdateRequest;
use App\Models\ProductVariant;
use App\Services\StockAlertService;
use App\Support\SearchPattern;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StockController extends Controller
{
    public const LOW_STOCK_THRESHOLD = 3;

    public function index(Request $request): Response
    {
        $search = trim($request->string('busca')->toString());
        $status = $request->string('status')->toString();

        if (! in_array($status, ['todos', 'disponivel', 'baixo', 'zerado'], true)) {
            $status = 'todos';
        }

        $variants = ProductVariant::query()
            ->with(['product.category'])
            ->when($search !== '', function ($query) use ($search) {
                $pattern = SearchPattern::contains($search);

                $query->where(function ($subQuery) use ($pattern) {
                    $subQuery
                        ->where('name', 'ilike', $pattern)
                        ->orWhere('sku', 'ilike', $pattern)
                        ->orWhereHas('product', function ($productQuery) use ($pattern) {
                            $productQuery
                                ->where('name', 'ilike', $pattern)
                                ->orWhereHas('category', function ($categoryQuery) use ($pattern) {
                                    $categoryQuery->where('name', 'ilike', $pattern);
                                });
                        });
                });
            })
            ->when($status === 'zerado', fn ($query) => $query->where('stock', '<=', 0))
            ->when(
                $status === 'baixo',
                fn ($query) => $query
                    ->where('stock', '>', 0)
                    ->where('stock', '<=', self::LOW_STOCK_THRESHOLD)
            )
            ->when(
                $status === 'disponivel',
                fn ($query) => $query->where('stock', '>', self::LOW_STOCK_THRESHOLD)
            )
            ->orderBy('stock', 'asc')
            ->orderBy('id', 'asc')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (ProductVariant $variant) => [
                'id' => $variant->id,
                'name' => $variant->name,
                'sku' => $variant->sku,
                'price' => $variant->price,
                'stock' => $variant->stock,
                'active' => $variant->active,
                'product' => $variant->product
                    ? [
                        'id' => $variant->product->id,
                        'name' => $variant->product->name,
                        'active' => $variant->product->active,
                        'category' => $variant->product->category
                            ? [
                                'id' => $variant->product->category->id,
                                'name' => $variant->product->category->name,
                            ]
                            : null,
                    ]
                    : null,
            ]);

        return Inertia::render('admin/stock/index', [
            'variants' => $variants,
            'stats' => [
                'total_units' => (int) ProductVariant::sum('stock'),
                'total_variants' => ProductVariant::count(),
                'low_stock' => ProductVariant::where('stock', '>', 0)
                    ->where('stock', '<=', self::LOW_STOCK_THRESHOLD)
                    ->count(),
                'out_of_stock' => ProductVariant::where('stock', '<=', 0)->count(),
                'low_stock_threshold' => self::LOW_STOCK_THRESHOLD,
            ],
            'filters' => [
                'busca' => $search,
                'status' => $status,
            ],
        ]);
    }

    public function update(
        StockUpdateRequest $request,
        ProductVariant $variant,
        StockAlertService $stockAlerts,
    ): RedirectResponse {
        $previousStock = $variant->stock;

        $variant->update([
            'stock' => $request->integer('stock'),
        ]);

        $notifiedCount = $stockAlerts->notifyRestockedVariant($variant->fresh(), $previousStock);

        $message = $notifiedCount > 0
            ? "Estoque atualizado com sucesso. {$notifiedCount} aviso(s) de reposição colocado(s) na fila."
            : 'Estoque atualizado com sucesso.';

        return back()->with('success', $message);
    }
}
