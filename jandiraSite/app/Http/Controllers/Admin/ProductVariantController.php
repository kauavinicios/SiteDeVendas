<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\ProductVariant;
use Illuminate\Validation\Rule;

class ProductVariantController extends Controller
{
    public function store(
        Request $request,
        Product $product
    ): RedirectResponse {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => [
                'nullable',
                'string',
                'max:100',
                'unique:product_variants,sku',
            ],
            'price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'active' => ['required', 'boolean'],
        ]);

        $validated['sku'] = $request->filled('sku')
            ? trim($validated['sku'])
            : null;

        $product->variants()->create($validated);

        return back()->with(
            'success',
            'Variação cadastrada com sucesso.'
        );
    }

    public function update(
        Request $request,
        Product $product,
        ProductVariant $variant
    ): RedirectResponse {
        abort_unless(
            $variant->product_id === $product->id,
            404
        );

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],

            'sku' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('product_variants', 'sku')
                    ->ignore($variant->id),
            ],

            'price' => [
                'required',
                'numeric',
                'min:0',
            ],

            'stock' => [
                'required',
                'integer',
                'min:0',
            ],

            'active' => [
                'required',
                'boolean',
            ],
        ]);

        $validated['sku'] = $request->filled('sku')
            ? trim($validated['sku'])
            : null;

        $variant->update($validated);

        return back()->with(
            'success',
            'Variação atualizada com sucesso.'
        );
    }
}