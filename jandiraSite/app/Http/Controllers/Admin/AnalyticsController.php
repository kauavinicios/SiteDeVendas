<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentTransactionStatus;
use App\Http\Controllers\Controller;
use App\Models\Favorite;
use App\Models\Order;
use App\Models\PaymentTransaction;
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
            ->whereHas('favorites')
            ->withCount('favorites')
            ->withSum('variants as total_stock', 'stock')
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
            ->whereHas('stockAlerts', fn ($query) => $query->whereNull('notified_at'))
            ->withCount([
                'stockAlerts as pending_alerts_count' => fn ($query) => $query->whereNull('notified_at'),
            ])
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

        $paymentMetrics = $this->buildPaymentMetrics($startDate);

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
            'paymentMetrics' => $paymentMetrics,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPaymentMetrics(CarbonImmutable $startDate): array
    {
        $transactions = PaymentTransaction::query()
            ->where('provider', 'infinitepay')
            ->where('created_at', '>=', $startDate)
            ->get();

        $createdLinks = $transactions->count();
        $paidTransactions = $transactions->filter(fn (PaymentTransaction $tx) => $tx->isPaid());
        $paidLinks = $paidTransactions->count();
        $pendingLinks = $transactions->where('status', PaymentTransactionStatus::Pending)->count();
        $failedOrCancelled = $transactions->filter(
            fn (PaymentTransaction $tx) => in_array($tx->status, [
                PaymentTransactionStatus::Failed,
                PaymentTransactionStatus::Cancelled,
                PaymentTransactionStatus::Refunded,
            ], true)
        )->count();

        $checkoutConversionRate = $createdLinks > 0
            ? round(($paidLinks / $createdLinks) * 100, 1)
            : 0.0;

        $paymentDurationsSeconds = [];
        $expectedPaidCents = 0;
        $actualPaidCents = 0;
        $methodAggregates = [];
        $installmentAggregates = [];
        $cardPaidCents = 0;
        $cardInstallmentsSum = 0;
        $cardTransactionsCount = 0;

        foreach ($paidTransactions as $tx) {
            if ($tx->created_at !== null && $tx->paid_at !== null && $tx->paid_at->greaterThanOrEqualTo($tx->created_at)) {
                $paymentDurationsSeconds[] = $tx->paid_at->timestamp - $tx->created_at->timestamp;
            }

            $expectedCents = (int) $tx->amount_cents;
            $paidCents = (int) ($tx->paid_amount_cents ?? $tx->amount_cents);

            $expectedPaidCents += $expectedCents;
            $actualPaidCents += $paidCents;

            $methodKey = filled($tx->capture_method) ? (string) $tx->capture_method : 'other';
            $methodLabel = match ($methodKey) {
                'pix' => 'Pix',
                'credit_card' => 'Cartão de crédito',
                'debit_card' => 'Cartão de débito',
                'other' => 'Outro / não informado',
                default => ucfirst(str_replace('_', ' ', $methodKey)),
            };

            if (! isset($methodAggregates[$methodKey])) {
                $methodAggregates[$methodKey] = [
                    'method' => $methodKey,
                    'label' => $methodLabel,
                    'count' => 0,
                    'revenue_cents' => 0,
                ];
            }

            $methodAggregates[$methodKey]['count'] += 1;
            $methodAggregates[$methodKey]['revenue_cents'] += $paidCents;

            if ($methodKey === 'credit_card') {
                $installments = max(1, (int) ($tx->installments ?? 1));
                $cardPaidCents += $paidCents;
                $cardInstallmentsSum += $installments;
                $cardTransactionsCount += 1;

                if (! isset($installmentAggregates[$installments])) {
                    $installmentAggregates[$installments] = [
                        'installments' => $installments,
                        'label' => $installments === 1 ? '1x (à vista)' : "{$installments}x",
                        'count' => 0,
                        'revenue_cents' => 0,
                    ];
                }

                $installmentAggregates[$installments]['count'] += 1;
                $installmentAggregates[$installments]['revenue_cents'] += $paidCents;
            }
        }

        $avgTimeToPayMinutes = count($paymentDurationsSeconds) > 0
            ? round((array_sum($paymentDurationsSeconds) / count($paymentDurationsSeconds)) / 60, 1)
            : null;

        $expectedPaidTotal = round($expectedPaidCents / 100, 2);
        $actualPaidTotal = round($actualPaidCents / 100, 2);

        $captureMethods = collect($methodAggregates)
            ->sortByDesc('revenue_cents')
            ->map(function (array $row) use ($paidLinks) {
                $revenue = round($row['revenue_cents'] / 100, 2);
                $count = $row['count'];

                return [
                    'method' => $row['method'],
                    'label' => $row['label'],
                    'count' => $count,
                    'revenue' => $revenue,
                    'percentage' => $paidLinks > 0 ? round(($count / $paidLinks) * 100, 1) : 0.0,
                    'average_ticket' => $count > 0 ? round($revenue / $count, 2) : 0.0,
                ];
            })
            ->values();

        ksort($installmentAggregates);

        $installmentsBreakdown = collect($installmentAggregates)
            ->map(function (array $row) use ($cardPaidCents) {
                $revenue = round($row['revenue_cents'] / 100, 2);

                return [
                    'installments' => $row['installments'],
                    'label' => $row['label'],
                    'count' => $row['count'],
                    'revenue' => $revenue,
                    'percentage' => $cardPaidCents > 0 ? round(($row['revenue_cents'] / $cardPaidCents) * 100, 1) : 0.0,
                ];
            })
            ->values();

        return [
            'funnel' => [
                'created_links' => $createdLinks,
                'paid_links' => $paidLinks,
                'pending_links' => $pendingLinks,
                'failed_or_cancelled' => $failedOrCancelled,
                'checkout_conversion_rate' => $checkoutConversionRate,
            ],
            'avg_time_to_pay_minutes' => $avgTimeToPayMinutes,
            'amounts' => [
                'expected_paid_total' => $expectedPaidTotal,
                'actual_paid_total' => $actualPaidTotal,
                'difference_total' => round($actualPaidTotal - $expectedPaidTotal, 2),
            ],
            'average_card_installments' => $cardTransactionsCount > 0
                ? round($cardInstallmentsSum / $cardTransactionsCount, 1)
                : null,
            'capture_methods' => $captureMethods,
            'installments_breakdown' => $installmentsBreakdown,
        ];
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
