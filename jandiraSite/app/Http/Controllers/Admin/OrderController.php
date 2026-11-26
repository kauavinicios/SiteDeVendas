<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class OrderController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim($request->string('busca')->toString());
        $status = $request->string('status')->toString();

        $allowedStatuses = ['todos', ...OrderStatus::values()];

        if (! in_array($status, $allowedStatuses, true)) {
            $status = 'todos';
        }

        $canViewFinancial = $request->user()?->hasPermission(Permission::ViewFinancialReports) ?? false;

        $orders = Order::query()
            ->with('items')
            ->when($search !== '', function ($query) use ($search) {
                $pattern = "%{$search}%";

                $query->where(function ($subQuery) use ($pattern) {
                    $subQuery
                        ->where('code', 'ilike', $pattern)
                        ->orWhere('customer_name', 'ilike', $pattern)
                        ->orWhere('customer_email', 'ilike', $pattern)
                        ->orWhere('customer_phone', 'ilike', $pattern);
                });
            })
            ->when($status !== 'todos', fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(function (Order $order) use ($canViewFinancial) {
                return [
                    'id' => $order->id,
                    'code' => $order->code,
                    'customer_name' => $order->customer_name,
                    'customer_email' => $order->customer_email,
                    'customer_phone' => $order->customer_phone,
                    'status' => $order->status->value,
                    'status_label' => $order->status->label(),
                    'status_tone' => $order->status->tone(),
                    'channel' => $order->channel,
                    'payment_method' => $order->payment_method,
                    'payment_status' => $order->payment_status,
                    'total' => (float) $order->total,
                    'total_cost' => $canViewFinancial && $order->total_cost !== null ? (float) $order->total_cost : null,
                    'estimated_profit' => $canViewFinancial ? $order->estimatedProfit() : null,
                    'items_count' => $order->items_count,
                    'notes' => $order->notes,
                    'stock_deducted' => $order->stock_deducted_at !== null,
                    'created_at' => $order->created_at?->format('d/m/Y H:i'),
                    'items' => $order->items->map(fn ($item) => [
                        'id' => $item->id,
                        'product_name' => $item->product_name,
                        'variant_name' => $item->variant_name,
                        'sku' => $item->sku,
                        'unit_price' => (float) $item->unit_price,
                        'quantity' => $item->quantity,
                        'subtotal' => (float) $item->subtotal,
                    ])->values(),
                ];
            });

        $revenueStatuses = array_map(
            fn (OrderStatus $case) => $case->value,
            array_filter(OrderStatus::cases(), fn (OrderStatus $case) => $case->countsAsRevenue())
        );

        return Inertia::render('admin/orders/index', [
            'orders' => $orders,
            'stats' => [
                'total' => Order::count(),
                'pending' => Order::where('status', OrderStatus::Pending->value)->count(),
                'delivered' => Order::where('status', OrderStatus::Delivered->value)->count(),
                'confirmed_revenue' => (float) Order::whereIn('status', $revenueStatuses)->sum('total'),
            ],
            'statusOptions' => OrderStatus::options(),
            'filters' => [
                'busca' => $search,
                'status' => $status,
            ],
            'canViewFinancial' => $canViewFinancial,
        ]);
    }

    public function updateStatus(Request $request, Order $order, OrderPaymentService $orderPaymentService): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in(OrderStatus::values())],
            'payment_status' => ['nullable', 'string', Rule::in(['pending', 'paid', 'refunded'])],
            'payment_method' => ['nullable', 'string', 'max:40'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'status.required' => 'Selecione um status para o pedido.',
            'status.in' => 'Selecione um status válido.',
            'payment_status.in' => 'Selecione um status de pagamento válido.',
        ]);

        $nextStatus = OrderStatus::from($validated['status']);

        try {
            DB::transaction(function () use ($order, $nextStatus, $validated, $orderPaymentService): void {
                $lockedOrder = Order::query()
                    ->with('items')
                    ->lockForUpdate()
                    ->findOrFail($order->id);

                $shouldConsumeStock = $nextStatus->consumesStock();
                $alreadyDeducted = $lockedOrder->stock_deducted_at !== null;

                if ($shouldConsumeStock && ! $alreadyDeducted) {
                    $orderPaymentService->deductStockForOrder($lockedOrder);
                } elseif (! $shouldConsumeStock && $alreadyDeducted) {
                    $orderPaymentService->restoreStockForOrder($lockedOrder);
                }

                $lockedOrder->status = $nextStatus;
                $lockedOrder->cancelled_at = $nextStatus === OrderStatus::Cancelled ? ($lockedOrder->cancelled_at ?? now()) : null;
                $lockedOrder->payment_status = $validated['payment_status'] ?? $lockedOrder->payment_status;
                $lockedOrder->payment_method = array_key_exists('payment_method', $validated)
                    ? (filled($validated['payment_method']) ? trim($validated['payment_method']) : null)
                    : $lockedOrder->payment_method;
                $lockedOrder->notes = array_key_exists('notes', $validated)
                    ? (filled($validated['notes']) ? trim($validated['notes']) : null)
                    : $lockedOrder->notes;

                $lockedOrder->save();
            });
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', "Pedido {$order->code} atualizado para \"{$nextStatus->label()}\".");
    }
}
