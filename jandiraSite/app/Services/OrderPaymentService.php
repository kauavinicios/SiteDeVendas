<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentTransactionStatus;
use App\Models\Order;
use App\Models\PaymentEvent;
use App\Models\PaymentTransaction;
use App\Models\ProductVariant;
use App\Services\Payments\InfinitePayException;
use App\Services\Payments\PaymentCheckResult;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class OrderPaymentService
{
    public function __construct(
        private readonly InfinitePayService $infinitePay,
    ) {}

    /**
     * Cria um link de checkout na InfinitePay e registra transação e evento iniciais.
     *
     * @param  array<string, mixed>  $extraOptions
     */
    public function initiateInfinitePayCheckout(
        Order $order,
        ?string $redirectUrl = null,
        ?string $webhookUrl = null,
        array $extraOptions = [],
    ): PaymentTransaction {
        $link = $this->infinitePay->createCheckoutLink($order, $redirectUrl, $webhookUrl, $extraOptions);

        return DB::transaction(function () use ($order, $link): PaymentTransaction {
            $transaction = PaymentTransaction::create([
                'order_id' => $order->id,
                'provider' => 'infinitepay',
                'driver' => $link->driver,
                'auth_mode' => $link->authMode,
                'order_nsu' => $link->orderNsu,
                'slug' => $link->slug,
                'checkout_url' => $link->url,
                'status' => PaymentTransactionStatus::Pending,
                'amount_cents' => $link->amountCents,
                'raw_create_payload' => $link->rawPayload,
            ]);

            $order->update([
                'channel' => 'infinitepay',
                'payment_status' => 'pending',
                'payment_reference' => $link->slug,
            ]);

            $this->recordEvent(
                transaction: $transaction,
                order: $order,
                source: 'link_created',
                eventType: 'checkout.created',
                payload: $link->rawPayload,
                externalEventId: $link->slug,
            );

            return $transaction;
        });
    }

    public function findTransactionByIdentifiers(
        ?string $orderNsu = null,
        ?string $slug = null,
        ?string $transactionNsu = null,
    ): ?PaymentTransaction {
        $slug = trim((string) $slug);
        $transactionNsu = trim((string) $transactionNsu);
        $orderNsu = trim((string) $orderNsu);

        if ($slug !== '') {
            $bySlug = PaymentTransaction::query()
                ->where('provider', 'infinitepay')
                ->where('slug', $slug)
                ->latest()
                ->first();

            if ($bySlug) {
                return $bySlug;
            }
        }

        if ($transactionNsu !== '') {
            $byTxNsu = PaymentTransaction::query()
                ->where('provider', 'infinitepay')
                ->where('transaction_nsu', $transactionNsu)
                ->latest()
                ->first();

            if ($byTxNsu) {
                return $byTxNsu;
            }
        }

        if ($orderNsu !== '') {
            return PaymentTransaction::query()
                ->where('provider', 'infinitepay')
                ->where('order_nsu', $orderNsu)
                ->latest()
                ->first();
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function recordEvent(
        ?PaymentTransaction $transaction,
        ?Order $order,
        string $source,
        string $eventType,
        array $payload = [],
        ?string $externalEventId = null,
        ?string $notes = null,
        bool $processed = true,
    ): PaymentEvent {
        return PaymentEvent::create([
            'payment_transaction_id' => $transaction?->id,
            'order_id' => $order?->id ?? $transaction?->order_id,
            'provider' => 'infinitepay',
            'source' => $source,
            'event_type' => $eventType,
            'external_event_id' => $externalEventId,
            'payload' => $payload,
            'processed_at' => $processed ? now() : null,
            'notes' => $notes,
        ]);
    }

    /**
     * Consulta o pagamento na InfinitePay (via payment_check) e sincroniza transação, pedido e estoque.
     *
     * @param  array<string, mixed>  $triggerPayload
     */
    public function verifyAndSyncTransaction(
        PaymentTransaction $transaction,
        ?string $transactionNsu = null,
        string $source = 'manual_check',
        array $triggerPayload = [],
    ): PaymentTransaction {
        $slug = trim((string) $transaction->slug);

        if ($slug === '') {
            throw new InfinitePayException('A transação não possui slug da InfinitePay para verificação.');
        }

        $effectiveTxNsu = trim((string) ($transactionNsu ?: $transaction->transaction_nsu ?: ''));

        $result = $this->infinitePay->checkPayment(
            orderNsu: $transaction->order_nsu,
            transactionNsu: $effectiveTxNsu,
            slug: $slug,
        );

        return $this->applyPaymentCheckResult($transaction, $result, $source, $triggerPayload);
    }

    /**
     * Aplica o resultado oficial de payment_check de forma transacional e idempotente.
     *
     * @param  array<string, mixed>  $triggerPayload
     */
    public function applyPaymentCheckResult(
        PaymentTransaction $transaction,
        PaymentCheckResult $result,
        string $source = 'manual_check',
        array $triggerPayload = [],
    ): PaymentTransaction {
        return DB::transaction(function () use ($transaction, $result, $source, $triggerPayload): PaymentTransaction {
            $lockedTx = PaymentTransaction::query()
                ->lockForUpdate()
                ->findOrFail($transaction->id);

            $lockedOrder = Order::query()
                ->with('items')
                ->lockForUpdate()
                ->findOrFail($lockedTx->order_id);

            $lockedTx->last_checked_at = now();
            $lockedTx->raw_last_check_payload = $result->rawPayload;

            if (filled($result->transactionNsu)) {
                $lockedTx->transaction_nsu = $result->transactionNsu;
            }

            if (filled($result->captureMethod)) {
                $lockedTx->capture_method = $result->captureMethod;
            }

            if ($result->installments !== null) {
                $lockedTx->installments = $result->installments;
            }

            if (filled($result->receiptUrl)) {
                $lockedTx->receipt_url = $result->receiptUrl;
            }

            if ($result->paidAmountCents !== null) {
                $lockedTx->paid_amount_cents = $result->paidAmountCents;
            }

            $eventPayload = [
                'check' => $result->rawPayload,
                'trigger' => $triggerPayload,
            ];

            if ($result->paid) {
                $wasAlreadyPaid = $lockedTx->isPaid();

                $lockedTx->status = PaymentTransactionStatus::Paid;
                $lockedTx->paid_at ??= now();

                $lockedOrder->payment_status = 'paid';

                if (filled($result->captureMethod)) {
                    $lockedOrder->payment_method = $result->captureMethod;
                }

                $lockedOrder->payment_reference = $lockedTx->transaction_nsu
                    ?: $lockedTx->slug
                    ?: $lockedOrder->payment_reference;

                if ($lockedOrder->status === OrderStatus::Pending) {
                    $lockedOrder->status = OrderStatus::Confirmed;
                    $lockedOrder->confirmed_at ??= now();
                }

                if ($lockedOrder->stock_deducted_at === null) {
                    try {
                        $this->deductStockForOrder($lockedOrder);
                    } catch (RuntimeException $exception) {
                        $this->recordEvent(
                            transaction: $lockedTx,
                            order: $lockedOrder,
                            source: $source,
                            eventType: 'stock.insufficient',
                            payload: $eventPayload,
                            externalEventId: $lockedTx->transaction_nsu,
                            notes: $exception->getMessage(),
                        );

                        $warningLine = '[Atenção estoque no pagamento] '.$exception->getMessage();

                        if (! str_contains((string) $lockedOrder->notes, $warningLine)) {
                            $lockedOrder->notes = filled($lockedOrder->notes)
                                ? $lockedOrder->notes."\n".$warningLine
                                : $warningLine;
                        }
                    }
                }

                $lockedTx->save();
                $lockedOrder->save();

                $this->recordEvent(
                    transaction: $lockedTx,
                    order: $lockedOrder,
                    source: $source,
                    eventType: $wasAlreadyPaid ? 'payment.paid_duplicate' : 'payment.paid',
                    payload: $eventPayload,
                    externalEventId: $lockedTx->transaction_nsu,
                );
            } else {
                $lockedTx->save();

                $this->recordEvent(
                    transaction: $lockedTx,
                    order: $lockedOrder,
                    source: $source,
                    eventType: 'payment.pending',
                    payload: $eventPayload,
                    externalEventId: $lockedTx->transaction_nsu,
                );
            }

            return $lockedTx->fresh(['order', 'events']);
        });
    }

    /**
     * Realiza baixa de estoque idempotente para um pedido dentro de uma transação ativa.
     */
    public function deductStockForOrder(Order $lockedOrder): void
    {
        if ($lockedOrder->stock_deducted_at !== null) {
            return;
        }

        $lockedOrder->loadMissing('items');

        $variantIds = $lockedOrder->items
            ->pluck('product_variant_id')
            ->filter()
            ->unique()
            ->values();

        $variants = ProductVariant::query()
            ->whereIn('id', $variantIds)
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($lockedOrder->items as $item) {
            if (! $item->product_variant_id) {
                continue;
            }

            $variant = $variants->get($item->product_variant_id);

            if (! $variant) {
                throw new RuntimeException("A variação \"{$item->product_name} — {$item->variant_name}\" não existe mais para baixa de estoque.");
            }

            if ($variant->stock < $item->quantity) {
                throw new RuntimeException("Estoque insuficiente para \"{$item->product_name} — {$item->variant_name}\" (disponível: {$variant->stock}, pedido: {$item->quantity}).");
            }
        }

        foreach ($lockedOrder->items as $item) {
            if (! $item->product_variant_id) {
                continue;
            }

            $variant = $variants->get($item->product_variant_id);

            if ($variant) {
                $variant->decrement('stock', $item->quantity);
            }
        }

        $lockedOrder->stock_deducted_at = now();
        $lockedOrder->confirmed_at ??= now();
    }

    /**
     * Devolve o estoque de forma idempotente quando o pedido volta a um status que não consome estoque.
     */
    public function restoreStockForOrder(Order $lockedOrder): void
    {
        if ($lockedOrder->stock_deducted_at === null) {
            return;
        }

        $lockedOrder->loadMissing('items');

        $variantIds = $lockedOrder->items
            ->pluck('product_variant_id')
            ->filter()
            ->unique()
            ->values();

        $variants = ProductVariant::query()
            ->whereIn('id', $variantIds)
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($lockedOrder->items as $item) {
            if (! $item->product_variant_id) {
                continue;
            }

            $variant = $variants->get($item->product_variant_id);

            if ($variant) {
                $variant->increment('stock', $item->quantity);
            }
        }

        $lockedOrder->stock_deducted_at = null;
    }
}
