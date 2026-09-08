<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $recentProducts = Product::with([
                'category',
                'variants',
            ])
            ->latest()
            ->take(5)
            ->get()
            ->map(function (Product $product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,

                    'category' => $product->category
                        ? [
                            'id' => $product->category->id,
                            'name' => $product->category->name,
                        ]
                        : null,

                    'stock' => $product->variants->sum('stock'),

                    'featured' => $product->featured,
                ];
            });

        return Inertia::render('dashboard', [
            'stats' => [
                'products' => Product::count(),
                'categories' => Category::count(),
                'stock' => ProductVariant::sum('stock'),
                'featured' => Product::where('featured', true)->count(),
            ],

            'recentProducts' => $recentProducts,
        ]);
    }
}