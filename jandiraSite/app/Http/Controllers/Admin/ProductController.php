<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim($request->string('busca')->toString());
        $status = $request->string('status')->toString();

        if (! in_array($status, ['todos', 'ativos', 'inativos', 'destaques'], true)) {
            $status = 'todos';
        }

        $products = Product::query()
            ->with([
                'category',
                'variants',
            ])
            ->when($search !== '', function ($query) use ($search) {
                $pattern = "%{$search}%";

                $query->where(function ($subQuery) use ($pattern) {
                    $subQuery
                        ->where('name', 'ilike', $pattern)
                        ->orWhere('slug', 'ilike', $pattern)
                        ->orWhereHas('category', function ($categoryQuery) use ($pattern) {
                            $categoryQuery->where('name', 'ilike', $pattern);
                        });
                });
            })
            ->when($status === 'ativos', fn ($query) => $query->where('active', true))
            ->when($status === 'inativos', fn ($query) => $query->where('active', false))
            ->when($status === 'destaques', fn ($query) => $query->where('featured', true))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/products/index', [
            'products' => $products,
            'filters' => [
                'busca' => $search,
                'status' => $status,
            ],
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
            'images',
        ]);

        return Inertia::render('admin/products/edit', [
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'category_id' => $product->category_id,
                'description' => $product->description,
                'active' => $product->active,
                'featured' => $product->featured,

                'images' => $product->images->map(function ($image) {
                    return [
                        'id' => $image->id,
                        'path' => $image->path,
                        'url' => $image->url,
                        'position' => $image->position,
                    ];
                })->values(),

                'variants' => $product->variants->map(function ($variant) {
                    return [
                        'id' => $variant->id,
                        'name' => $variant->name,
                        'sku' => $variant->sku,
                        'price' => $variant->price,
                        'cost_price' => request()->user()?->hasPermission(Permission::ViewFinancialReports)
                            ? $variant->cost_price
                            : null,
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
