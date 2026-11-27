@extends('layouts.app')

@section('title', 'Simulador InfinitePay (Local) - Butique da Jandira')

@section('content')

    <section class="py-10 md:py-14">
        <div class="store-container max-w-3xl">

            <div class="rounded-3xl border border-[var(--color-border)] bg-white p-6 shadow-[var(--shadow-md)] md:p-8">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-[var(--color-border)] pb-5">
                    <div>
                        <span class="store-eyebrow">Simulador local &bull; INFINITEPAY_DRIVER=mock</span>
                        <h1 class="mt-1.5 text-2xl font-semibold text-[var(--color-text)] md:text-3xl">
                            Checkout Simulado InfinitePay
                        </h1>
                    </div>

                    <span class="inline-flex rounded-full bg-[var(--color-surface-soft)] px-3 py-1 text-xs font-semibold text-[var(--color-primary)]">
                        Slug: {{ $slug }}
                    </span>
                </div>

                <p class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs leading-relaxed text-amber-900">
                    Este ambiente simula a página oficial da InfinitePay para que você teste todo o fluxo de compra,
                    baixa de estoque e métricas sem gerar cobranças reais.
                </p>

                @php
                    $orderCode = $order?->code ?? ($cached['order_nsu'] ?? $slug);
                    $amountReais = $transaction?->amountInReais() ?? (isset($cached['amount']) ? round(((int) $cached['amount']) / 100, 2) : 0.0);
                @endphp

                <div class="mt-6 rounded-2xl border border-[var(--color-border)] bg-[var(--color-page-soft)] p-5">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold tracking-wider text-[var(--color-text-muted)] uppercase">Pedido</p>
                            <p class="mt-0.5 text-lg font-semibold text-[var(--color-text)]">#{{ $orderCode }}</p>
                        </div>

                        <div class="text-right">
                            <p class="text-xs font-semibold tracking-wider text-[var(--color-text-muted)] uppercase">Valor a pagar</p>
                            <p class="mt-0.5 text-2xl font-semibold text-[var(--color-primary)]">
                                R$ {{ number_format($amountReais, 2, ',', '.') }}
                            </p>
                        </div>
                    </div>

                    @if ($order && $order->items->isNotEmpty())
                        <ul class="mt-4 divide-y divide-[var(--color-border)] border-t border-[var(--color-border)] pt-2 text-sm">
                            @foreach ($order->items as $item)
                                <li class="flex items-center justify-between gap-4 py-2">
                                    <span class="text-[var(--color-text-muted)]">
                                        {{ $item->quantity }}x {{ $item->product_name }} ({{ $item->variant_name }})
                                    </span>
                                    <span class="font-medium text-[var(--color-text)]">
                                        R$ {{ number_format((float) $item->subtotal, 2, ',', '.') }}
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                <form action="{{ route('dev.infinitepay.simulate', ['slug' => $slug]) }}" method="POST" class="mt-7 space-y-3">
                    @csrf

                    <p class="text-sm font-medium text-[var(--color-text)]">
                        Escolha o resultado que deseja simular:
                    </p>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <button type="submit" name="action" value="pay_pix" class="store-button-primary w-full">
                            Aprovar via Pix
                        </button>

                        <button type="submit" name="action" value="pay_card_1x" class="store-button-primary w-full">
                            Aprovar Cartão 1x
                        </button>

                        <button type="submit" name="action" value="pay_card_3x" class="store-button-primary w-full">
                            Aprovar Cartão 3x
                        </button>

                        <button type="submit" name="action" value="cancel" class="store-button-secondary w-full">
                            Voltar sem pagar
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </section>

@endsection
