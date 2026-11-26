<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockAlert;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
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

        $canViewFinancial = $request->user()?->hasPermission(Permission::ViewFinancialReports) ?? false;

        $revenueStatuses = array_map(
            fn (OrderStatus $case) => $case->value,
            array_filter(OrderStatus::cases(), fn (OrderStatus $case) => $case->countsAsRevenue())
        );

        $orders30d = Order::query()
            ->with('items')
            ->where('created_at', '>=', now()->subDays(29)->startOfDay())
            ->whereIn('status', $revenueStatuses)
            ->get();

        $revenue30d = round((float) $orders30d->sum('total'), 2);
        $profit30d = 0.0;

        if ($canViewFinancial) {
            foreach ($orders30d as $order) {
                foreach ($order->items as $item) {
                    if ($item->unit_cost !== null) {
                        $profit30d += (float) $item->subtotal - ((float) $item->unit_cost * $item->quantity);
                    }
                }
            }
        }

        return Inertia::render('dashboard', [
            'stats' => [
                'products' => Product::count(),
                'categories' => Category::count(),
                'stock' => ProductVariant::sum('stock'),
                'featured' => Product::where('featured', true)->count(),
            ],

            'summary' => [
                'pending_orders' => Order::where('status', OrderStatus::Pending->value)->count(),
                'confirmed_orders_30d' => $orders30d->count(),
                'pending_stock_alerts' => StockAlert::whereNull('notified_at')->count(),
                'revenue_30d' => $canViewFinancial ? $revenue30d : null,
                'estimated_profit_30d' => $canViewFinancial ? round($profit30d, 2) : null,
            ],

            'recentProducts' => $recentProducts,
        ]);
    }
}
