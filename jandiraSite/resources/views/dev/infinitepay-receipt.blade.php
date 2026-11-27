@extends('layouts.app')

@section('title', 'Comprovante Simulado InfinitePay - Butique da Jandira')

@section('content')

    <section class="py-10 md:py-14">
        <div class="store-container max-w-2xl">

            @php
                $orderCode = $order?->code ?? ($cached['order_nsu'] ?? $slug);
                $txNsu = $transaction?->transaction_nsu ?? ($cached['transaction_nsu'] ?? '—');
                $method = $transaction?->captureMethodLabel() ?? ($cached['capture_method'] ?? 'Pix');
                $installments = $transaction?->installments ?? ($cached['installments'] ?? 1);
                $paidAmount = $transaction?->paidAmountInReais()
                    ?? (isset($cached['paid_amount']) ? round(((int) $cached['paid_amount']) / 100, 2) : (float) ($order?->total ?? 0));
            @endphp

            <div class="rounded-3xl border border-[var(--color-border)] bg-white p-6 shadow-[var(--shadow-sm)] md:p-8">
                <span class="store-eyebrow">Comprovante simulado (ambiente local)</span>

                <h1 class="mt-2 text-2xl font-semibold text-[var(--color-text)] md:text-3xl">
                    Recibo do Pagamento #{{ $orderCode }}
                </h1>

                <dl class="mt-6 space-y-3 rounded-2xl border border-[var(--color-border)] bg-[var(--color-page-soft)] p-5 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-[var(--color-text-muted)]">Estabelecimento</dt>
                        <dd class="font-medium text-[var(--color-text)]">Butique da Jandira — Casa &amp; Perfumaria</dd>
                    </div>

                    <div class="flex justify-between gap-4">
                        <dt class="text-[var(--color-text-muted)]">Método</dt>
                        <dd class="font-medium text-[var(--color-text)]">
                            {{ $method }}
                            @if ($installments > 1)
                                ({{ $installments }}x)
                            @endif
                        </dd>
                    </div>

                    <div class="flex justify-between gap-4">
                        <dt class="text-[var(--color-text-muted)]">Valor pago</dt>
                        <dd class="font-semibold text-[var(--color-primary)]">
                            R$ {{ number_format($paidAmount, 2, ',', '.') }}
                        </dd>
                    </div>

                    <div class="flex justify-between gap-4">
                        <dt class="text-[var(--color-text-muted)]">Slug</dt>
                        <dd class="font-mono text-xs text-[var(--color-text)]">{{ $slug }}</dd>
                    </div>

                    <div class="flex justify-between gap-4">
                        <dt class="text-[var(--color-text-muted)]">NSU da transação</dt>
                        <dd class="font-mono text-xs text-[var(--color-text)]">{{ $txNsu }}</dd>
                    </div>
                </dl>

                <div class="mt-7 flex flex-wrap gap-3">
                    <a href="{{ route('home') }}" class="store-button-primary">
                        Voltar para a loja
                    </a>

                    @auth
                        <a href="{{ route('account.index') }}" class="store-button-secondary">
                            Minha conta
                        </a>
                    @endauth
                </div>
            </div>

        </div>
    </section>

@endsection
