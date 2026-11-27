<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PaymentEvent;
use App\Services\OrderPaymentService;
use App\Services\Payments\InfinitePayException;
use App\Support\SearchPattern;
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
            ->with([
                'items',
                'latestPaymentTransaction',
                'paymentEvents' => fn ($query) => $query->latest()->limit(10),
            ])
            ->when($search !== '', function ($query) use ($search) {
                $pattern = SearchPattern::contains($search);

                $query->where(function ($subQuery) use ($pattern) {
                    $subQuery
                        ->where('code', 'ilike', $pattern)
                        ->orWhere('customer_name', 'ilike', $pattern)
                        ->orWhere('customer_email', 'ilike', $pattern)
                        ->orWhere('customer_phone', 'ilike', $pattern)
                        ->orWhere('payment_reference', 'ilike', $pattern);
                });
            })
            ->when($status !== 'todos', fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(function (Order $order) use ($canViewFinancial) {
                $transaction = $order->latestPaymentTransaction;

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
                    'payment_method_label' => $transaction?->captureMethodLabel() ?? $this->formatPaymentMethodLabel($order->payment_method),
                    'payment_status' => $order->payment_status,
                    'total' => (float) $order->total,
                    'total_cost' => $canViewFinancial && $order->total_cost !== null ? (float) $order->total_cost : null,
                    'estimated_profit' => $canViewFinancial ? $order->estimatedProfit() : null,
                    'items_count' => $order->items_count,
                    'notes' => $order->notes,
                    'stock_deducted' => $order->stock_deducted_at !== null,
                    'created_at' => $order->created_at?->format('d/m/Y H:i'),
                    'payment_transaction' => $transaction ? [
                        'id' => $transaction->id,
                        'provider' => $transaction->provider,
                        'driver' => $transaction->driver,
                        'auth_mode' => $transaction->auth_mode,
                        'status' => $transaction->status->value,
                        'status_label' => $transaction->status->label(),
                        'status_tone' => $transaction->status->tone(),
                        'slug' => $transaction->slug,
                        'transaction_nsu' => $transaction->transaction_nsu,
                        'capture_method' => $transaction->capture_method,
                        'capture_method_label' => $transaction->captureMethodLabel(),
                        'installments' => $transaction->installments,
                        'amount' => $transaction->amountInReais(),
                        'paid_amount' => $transaction->paidAmountInReais(),
                        'checkout_url' => $transaction->checkout_url,
                        'receipt_url' => $transaction->receipt_url,
                        'paid_at' => $transaction->paid_at?->format('d/m/Y H:i'),
                        'last_checked_at' => $transaction->last_checked_at?->format('d/m/Y H:i'),
                    ] : null,
                    'payment_events' => $order->paymentEvents->map(fn (PaymentEvent $event) => [
                        'id' => $event->id,
                        'source' => $event->source,
                        'source_label' => $this->formatEventSourceLabel($event->source),
                        'event_type' => $event->event_type,
                        'event_label' => $this->formatEventTypeLabel($event->event_type),
                        'external_event_id' => $event->external_event_id,
                        'notes' => $event->notes,
                        'created_at' => $event->created_at?->format('d/m/Y H:i'),
                    ])->values(),
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

    public function verifyPayment(
        Request $request,
        Order $order,
        OrderPaymentService $orderPaymentService,
    ): RedirectResponse {
        $transaction = $order->latestPaymentTransaction;

        if ($transaction === null) {
            return back()->with('error', 'Este pedido não possui uma transação InfinitePay vinculada.');
        }

        try {
            $synced = $orderPaymentService->verifyAndSyncTransaction(
                transaction: $transaction,
                transactionNsu: $transaction->transaction_nsu,
                source: 'manual_check',
                triggerPayload: [
                    'triggered_by' => $request->user()?->id,
                ],
            );
        } catch (InfinitePayException $exception) {
            $orderPaymentService->recordEvent(
                transaction: $transaction,
                order: $order,
                source: 'manual_check',
                eventType: 'payment.check_failed',
                payload: [
                    'triggered_by' => $request->user()?->id,
                    'response' => $exception->responseBody,
                ],
                externalEventId: $transaction->transaction_nsu ?? $transaction->slug,
                notes: $exception->getMessage(),
                processed: false,
            );

            return back()->with('error', 'Não foi possível consultar a InfinitePay: '.$exception->getMessage());
        }

        if ($synced->isPaid()) {
            return back()->with('success', "Pagamento do pedido {$order->code} confirmado pela InfinitePay!");
        }

        return back()->with('success', "Consulta concluída para {$order->code}: pagamento ainda pendente na InfinitePay.");
    }

    private function formatPaymentMethodLabel(?string $method): ?string
    {
        if ($method === null || trim($method) === '') {
            return null;
        }

        return match ($method) {
            'pix' => 'Pix',
            'credit_card' => 'Cartão de crédito',
            'debit_card' => 'Cartão de débito',
            default => $method,
        };
    }

    private function formatEventSourceLabel(string $source): string
    {
        return match ($source) {
            'link_created' => 'Link criado',
            'redirect_return' => 'Retorno do cliente',
            'webhook' => 'Webhook InfinitePay',
            'manual_check' => 'Consulta manual',
            default => ucfirst(str_replace('_', ' ', $source)),
        };
    }

    private function formatEventTypeLabel(string $eventType): string
    {
        return match ($eventType) {
            'checkout.created' => 'Link de pagamento gerado',
            'webhook.received' => 'Notificação recebida',
            'webhook.unmatched' => 'Webhook sem pedido correspondente',
            'webhook.check_failed' => 'Falha ao validar webhook',
            'payment.pending' => 'Pagamento ainda pendente',
            'payment.paid' => 'Pagamento confirmado',
            'payment.paid_duplicate' => 'Confirmação repetida (já pago)',
            'payment.check_failed' => 'Falha ao consultar API',
            'stock.insufficient' => 'Alerta: estoque insuficiente na confirmação',
            default => $eventType,
        };
    }
}
