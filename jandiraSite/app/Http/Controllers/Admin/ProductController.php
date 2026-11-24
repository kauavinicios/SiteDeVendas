<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(): Response
    {
        $products = Product::query()
            ->with([
                'category',
                'variants',
            ])
            ->latest()
            ->paginate(15);

        return Inertia::render('admin/products/index', [
            'products' => $products,
        ]);
    }

    public function create(): Response
    {
        $categories = Category::query()
            ->whereNull('parent_id')
            ->with('children.children')
            ->orderBy('name')
            ->get();

        return Inertia::render('admin/products/create', [
            'categories' => $categories,
        ]);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $validated['slug'] = Product::generateUniqueSlug($validated['name']);

        Product::create($validated);

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Produto cadastrado com sucesso.');
    }

    public function edit(Product $product): Response
    {
        $categories = Category::query()
            ->whereNull('parent_id')
            ->with('children.children')
            ->orderBy('name')
            ->get();

        $product->load([
            'variants' => function ($query) {
                $query->orderBy('id');
            },
        ]);

        return Inertia::render('admin/products/edit', [
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'category_id' => $product->category_id,
                'description' => $product->description,
                'active' => $product->active,
                'featured' => $product->featured,

                'variants' => $product->variants->map(function ($variant) {
                    return [
                        'id' => $variant->id,
                        'name' => $variant->name,
                        'sku' => $variant->sku,
                        'price' => $variant->price,
                        'stock' => $variant->stock,
                        'active' => $variant->active,
                    ];
                }),
            ],

            'categories' => $categories,
        ]);
    }

    public function update(
        ProductRequest $request,
        Product $product
    ): RedirectResponse {
        $validated = $request->validated();

        $validated['slug'] = Product::generateUniqueSlug($validated['name'], $product->id);

        $product->update($validated);

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Produto atualizado com sucesso.');
    }
}
