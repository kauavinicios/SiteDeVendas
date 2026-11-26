<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductVariantRequest;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\StockAlertService;
use Illuminate\Http\RedirectResponse;

class ProductVariantController extends Controller
{
    public function store(
        ProductVariantRequest $request,
        Product $product
    ): RedirectResponse {
        $product->variants()->create($request->variantData());

        return back()->with(
            'success',
            'Variação cadastrada com sucesso.'
        );
    }

    public function update(
        ProductVariantRequest $request,
        Product $product,
        ProductVariant $variant,
        StockAlertService $stockAlerts,
    ): RedirectResponse {
        $this->ensureVariantBelongsToProduct($product, $variant);

        $previousStock = $variant->stock;

        $variant->update($request->variantData());

        $stockAlerts->notifyRestockedVariant($variant->fresh(), $previousStock);

        return back()->with(
            'success',
            'Variação atualizada com sucesso.'
        );
    }

    /**
     * Defesa extra: a rota já usa scoped bindings (routes/admin.php).
     */
    private function ensureVariantBelongsToProduct(
        Product $product,
        ProductVariant $variant
    ): void {
        abort_unless($variant->product_id === $product->id, 404);
    }
}
