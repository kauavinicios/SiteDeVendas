<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Favorite;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockAlert;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsController extends Controller
{
    public function index(Request $request): Response
    {
        $period = $request->string('periodo')->toString();

        if (! in_array($period, ['7d', '30d', '90d', '365d'], true)) {
            $period = '30d';
        }

        $now = CarbonImmutable::now();

        [$startDate, $groupByMonth, $periodLabel] = match ($period) {
            '7d' => [$now->subDays(6)->startOfDay(), false, 'Últimos 7 dias'],
            '90d' => [$now->subMonths(2)->startOfMonth(), true, 'Últimos 3 meses'],
            '365d' => [$now->subMonths(11)->startOfMonth(), true, 'Últimos 12 meses'],
            default => [$now->subDays(29)->startOfDay(), false, 'Últimos 30 dias'],
        };

        $orders = Order::query()
            ->with(['items.product.category'])
            ->where('created_at', '>=', $startDate)
            ->orderBy('created_at')
            ->get();

        $revenueOrders = $orders->filter(fn (Order $order) => $order->status->countsAsRevenue());
        $pendingOrdersCount = $orders->where('status', OrderStatus::Pending)->count();
        $cancelledOrdersCount = $orders->where('status', OrderStatus::Cancelled)->count();
        $confirmedOrdersCount = $revenueOrders->count();
        $totalOrdersCount = $orders->count();

        $grossRevenue = round((float) $revenueOrders->sum('total'), 2);
        $averageTicket = $confirmedOrdersCount > 0 ? round($grossRevenue / $confirmedOrdersCount, 2) : 0.0;
        $conversionRate = $totalOrdersCount > 0 ? round(($confirmedOrdersCount / $totalOrdersCount) * 100, 1) : 0.0;

        $revenueWithCost = 0.0;
        $estimatedCost = 0.0;
        $itemsWithoutCost = 0;

        $seriesBuckets = $this->buildEmptyBuckets($startDate, $now, $groupByMonth);
        $productAggregates = [];
        $categoryAggregates = [];

        foreach ($revenueOrders as $order) {
            $bucketKey = $groupByMonth
                ? $order->created_at->format('Y-m')
                : $order->created_at->format('Y-m-d');

            if (isset($seriesBuckets[$bucketKey])) {
                $seriesBuckets[$bucketKey]['revenue'] += (float) $order->total;
                $seriesBuckets[$bucketKey]['orders'] += 1;
            }

            foreach ($order->items as $item) {
                $itemRevenue = (float) $item->subtotal;
                $itemProfit = null;

                if ($item->unit_cost !== null) {
                    $itemCost = round((float) $item->unit_cost * $item->quantity, 2);
                    $revenueWithCost += $itemRevenue;
                    $estimatedCost += $itemCost;
                    $itemProfit = round($itemRevenue - $itemCost, 2);

                    if (isset($seriesBuckets[$bucketKey])) {
                        $seriesBuckets[$bucketKey]['profit'] += $itemProfit;
                    }
                } else {
                    $itemsWithoutCost += $item->quantity;
                }

                $productKey = $item->product_id ? (string) $item->product_id : 'name:'.$item->product_name;

                if (! isset($productAggregates[$productKey])) {
                    $productAggregates[$productKey] = [
                        'name' => $item->product_name,
                        'units' => 0,
                        'revenue' => 0.0,
                        'profit' => 0.0,
                        'has_cost' => false,
                    ];
                }

                $productAggregates[$productKey]['units'] += $item->quantity;
                $productAggregates[$productKey]['revenue'] += $itemRevenue;

                if ($itemProfit !== null) {
                    $productAggregates[$productKey]['profit'] += $itemProfit;
                    $productAggregates[$productKey]['has_cost'] = true;
                }

                $categoryName = $item->product?->category?->name ?? 'Sem categoria';

                if (! isset($categoryAggregates[$categoryName])) {
                    $categoryAggregates[$categoryName] = [
                        'name' => $categoryName,
                        'units' => 0,
                        'revenue' => 0.0,
                    ];
                }

                $categoryAggregates[$categoryName]['units'] += $item->quantity;
                $categoryAggregates[$categoryName]['revenue'] += $itemRevenue;
            }
        }

        $estimatedProfit = round($revenueWithCost - $estimatedCost, 2);
        $marginPercent = $revenueWithCost > 0
            ? round(($estimatedProfit / $revenueWithCost) * 100, 1)
            : null;

        $revenueSeries = array_values(array_map(fn (array $bucket) => [
            'label' => $bucket['label'],
            'revenue' => round($bucket['revenue'], 2),
            'profit' => round($bucket['profit'], 2),
            'orders' => $bucket['orders'],
        ], $seriesBuckets));

        $statusBreakdown = array_values(array_map(function (OrderStatus $case) use ($orders, $totalOrdersCount) {
            $count = $orders->where('status', $case)->count();

            return [
                'status' => $case->value,
                'label' => $case->label(),
                'tone' => $case->tone(),
                'count' => $count,
                'percentage' => $totalOrdersCount > 0 ? round(($count / $totalOrdersCount) * 100, 1) : 0.0,
            ];
        }, OrderStatus::cases()));

        $topProducts = collect($productAggregates)
            ->sortByDesc('revenue')
            ->take(6)
            ->map(fn (array $row) => [
                'name' => $row['name'],
                'units' => $row['units'],
                'revenue' => round($row['revenue'], 2),
                'profit' => $row['has_cost'] ? round($row['profit'], 2) : null,
            ])
            ->values();

        $topCategories = collect($categoryAggregates)
            ->sortByDesc('revenue')
            ->take(6)
            ->map(fn (array $row) => [
                'name' => $row['name'],
                'units' => $row['units'],
                'revenue' => round($row['revenue'], 2),
            ])
            ->values();

        $mostFavorited = Product::query()
            ->where('active', true)
            ->withCount('favorites')
            ->withSum('variants as total_stock', 'stock')
            ->having('favorites_count', '>', 0)
            ->orderByDesc('favorites_count')
            ->take(5)
            ->get()
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'favorites_count' => (int) $product->favorites_count,
                'stock' => (int) ($product->total_stock ?? 0),
            ])
            ->values();

        $mostRequestedRestock = ProductVariant::query()
            ->with('product')
            ->withCount([
                'stockAlerts as pending_alerts_count' => fn ($query) => $query->whereNull('notified_at'),
            ])
            ->having('pending_alerts_count', '>', 0)
            ->orderByDesc('pending_alerts_count')
            ->take(5)
            ->get()
            ->map(fn (ProductVariant $variant) => [
                'variant_id' => $variant->id,
                'product_name' => $variant->product?->name ?? 'Produto',
                'variant_name' => $variant->name,
                'stock' => $variant->stock,
                'pending_alerts' => (int) $variant->pending_alerts_count,
            ])
            ->values();

        return Inertia::render('admin/analytics/index', [
            'period' => $period,
            'periodLabel' => $periodLabel,
            'kpis' => [
                'gross_revenue' => $grossRevenue,
                'revenue_with_cost' => round($revenueWithCost, 2),
                'estimated_cost' => round($estimatedCost, 2),
                'estimated_profit' => $estimatedProfit,
                'margin_percent' => $marginPercent,
                'items_without_cost' => $itemsWithoutCost,
                'total_orders' => $totalOrdersCount,
                'confirmed_orders' => $confirmedOrdersCount,
                'pending_orders' => $pendingOrdersCount,
                'cancelled_orders' => $cancelledOrdersCount,
                'average_ticket' => $averageTicket,
                'conversion_rate' => $conversionRate,
                'pending_stock_alerts' => StockAlert::whereNull('notified_at')->count(),
                'total_favorites' => Favorite::count(),
            ],
            'revenueSeries' => $revenueSeries,
            'statusBreakdown' => $statusBreakdown,
            'topProducts' => $topProducts,
            'topCategories' => $topCategories,
            'mostFavorited' => $mostFavorited,
            'mostRequestedRestock' => $mostRequestedRestock,
        ]);
    }

    /**
     * @return array<string, array{label: string, revenue: float, profit: float, orders: int}>
     */
    private function buildEmptyBuckets(CarbonImmutable $startDate, CarbonImmutable $endDate, bool $groupByMonth): array
    {
        $buckets = [];
        $cursor = $groupByMonth ? $startDate->startOfMonth() : $startDate->startOfDay();
        $limit = $groupByMonth ? $endDate->startOfMonth() : $endDate->startOfDay();

        $monthsPt = [
            1 => 'Jan', 2 => 'Fev', 3 => 'Mar', 4 => 'Abr',
            5 => 'Mai', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago',
            9 => 'Set', 10 => 'Out', 11 => 'Nov', 12 => 'Dez',
        ];

        while ($cursor->lessThanOrEqualTo($limit)) {
            if ($groupByMonth) {
                $key = $cursor->format('Y-m');
                $label = $monthsPt[$cursor->month].'/'.$cursor->format('y');
                $cursor = $cursor->addMonth();
            } else {
                $key = $cursor->format('Y-m-d');
                $label = $cursor->format('d/m');
                $cursor = $cursor->addDay();
            }

            $buckets[$key] = [
                'label' => $label,
                'revenue' => 0.0,
                'profit' => 0.0,
                'orders' => 0,
            ];
        }

        return $buckets;
    }
}
