<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductVariantRequest;
use App\Models\Product;
use App\Models\ProductVariant;
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
        ProductVariant $variant
    ): RedirectResponse {
        $this->ensureVariantBelongsToProduct($product, $variant);

        $variant->update($request->variantData());

        return back()->with(
            'success',
            'Variação atualizada com sucesso.'
        );
    }

    public function destroy(
        Product $product,
        ProductVariant $variant
    ): RedirectResponse {
        $this->ensureVariantBelongsToProduct($product, $variant);

        $variant->delete();

        return back()->with(
            'success',
            'Variação excluída com sucesso.'
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
