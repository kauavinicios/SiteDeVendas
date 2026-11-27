@extends('layouts.app')

@section('title', 'Retorno do pagamento - Butique da Jandira')

@section('content')

    <section class="py-10 md:py-14">
        <div class="store-container max-w-3xl">

            @if ($transaction && $order)
                @if ($transaction->isPaid())
                    <div class="rounded-3xl border border-[#c8dfd0] bg-white p-6 shadow-[var(--shadow-sm)] md:p-8">
                        <span class="inline-flex rounded-full bg-[#eef7f1] px-3 py-1 text-xs font-semibold text-[#2f6241]">
                            Pagamento confirmado
                        </span>

                        <h1 class="mt-4 text-2xl font-semibold text-[var(--color-text)] md:text-3xl">
                            Obrigada pelo seu pedido, {{ $order->customer_name }}!
                        </h1>

                        <p class="mt-2 text-sm leading-relaxed text-[var(--color-text-muted)]">
                            Recebemos a confirmação do seu pagamento pela InfinitePay para o pedido
                            <strong class="text-[var(--color-text)]">#{{ $order->code }}</strong>.
                            Nossa equipe já pode dar sequência ao atendimento e preparação.
                        </p>

                        <dl class="mt-6 grid gap-4 rounded-2xl border border-[var(--color-border)] bg-[var(--color-page-soft)] p-5 text-sm sm:grid-cols-2 lg:grid-cols-3">
                            <div>
                                <dt class="text-xs font-semibold tracking-wider text-[var(--color-text-muted)] uppercase">Pedido</dt>
                                <dd class="mt-1 font-semibold text-[var(--color-text)]">#{{ $order->code }}</dd>
                            </div>

                            <div>
                                <dt class="text-xs font-semibold tracking-wider text-[var(--color-text-muted)] uppercase">Forma de pagamento</dt>
                                <dd class="mt-1 font-semibold text-[var(--color-text)]">
                                    {{ $transaction->captureMethodLabel() ?? 'InfinitePay' }}
                                    @if ($transaction->installments && $transaction->installments > 1)
                                        ({{ $transaction->installments }}x)
                                    @endif
                                </dd>
                            </div>

                            <div>
                                <dt class="text-xs font-semibold tracking-wider text-[var(--color-text-muted)] uppercase">Valor pago</dt>
                                <dd class="mt-1 font-semibold text-[var(--color-primary)]">
                                    R$ {{ number_format($transaction->paidAmountInReais() ?? (float) $order->total, 2, ',', '.') }}
                                </dd>
                            </div>
                        </dl>

                        <div class="mt-6 rounded-2xl border border-[var(--color-border)] p-5">
                            <h2 class="text-sm font-semibold text-[var(--color-text)]">Resumo dos itens</h2>

                            <ul class="mt-3 divide-y divide-[var(--color-border)] text-sm">
                                @foreach ($order->items as $item)
                                    <li class="flex items-center justify-between gap-4 py-2.5">
                                        <div>
                                            <p class="font-medium text-[var(--color-text)]">{{ $item->product_name }}</p>
                                            <p class="text-xs text-[var(--color-text-muted)]">
                                                {{ $item->variant_name }} &bull; Qtd: {{ $item->quantity }}
                                            </p>
                                        </div>
                                        <span class="font-semibold text-[var(--color-text)]">
                                            R$ {{ number_format((float) $item->subtotal, 2, ',', '.') }}
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        <div class="mt-7 flex flex-wrap gap-3">
                            @if ($transaction->receipt_url)
                                <a href="{{ $transaction->receipt_url }}" target="_blank" rel="noopener noreferrer"
                                    class="store-button-primary">
                                    Ver comprovante oficial
                                </a>
                            @endif

                            @auth
                                <a href="{{ route('account.index') }}" class="store-button-secondary">
                                    Acompanhar em Minha conta
                                </a>
                            @endauth

                            <a href="{{ route('products.index') }}" class="store-button-secondary">
                                Ver mais produtos
                            </a>
                        </div>
                    </div>
                @else
                    <div class="rounded-3xl border border-[#ebd7b0] bg-white p-6 shadow-[var(--shadow-sm)] md:p-8">
                        <span class="inline-flex rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-800">
                            Aguardando confirmação
                        </span>

                        <h1 class="mt-4 text-2xl font-semibold text-[var(--color-text)] md:text-3xl">
                            Pedido #{{ $order->code }} ainda aguarda pagamento
                        </h1>

                        <p class="mt-2 text-sm leading-relaxed text-[var(--color-text-muted)]">
                            {{ $verificationWarning ?? 'Ainda não recebemos a confirmação de quitação pela InfinitePay. Se você acabou de concluir um Pix ou cartão, aguarde alguns instantes e verifique novamente.' }}
                        </p>

                        <div class="mt-7 flex flex-wrap gap-3">
                            @if ($transaction->checkout_url)
                                <a href="{{ $transaction->checkout_url }}" class="store-button-primary">
                                    Retomar pagamento na InfinitePay
                                </a>
                            @endif

                            <a href="{{ request()->fullUrl() }}" class="store-button-secondary">
                                Verificar pagamento novamente
                            </a>

                            @auth
                                <a href="{{ route('account.index') }}" class="store-button-secondary">
                                    Ir para Minha conta
                                </a>
                            @endauth
                        </div>
                    </div>
                @endif
            @else
                <div class="rounded-3xl border border-[var(--color-border)] bg-white p-6 shadow-[var(--shadow-sm)] md:p-8">
                    <span class="store-eyebrow">Retorno do checkout</span>

                    <h1 class="mt-3 text-2xl font-semibold text-[var(--color-text)] md:text-3xl">
                        Não foi possível localizar esta transação
                    </h1>

                    <p class="mt-2 text-sm leading-relaxed text-[var(--color-text-muted)]">
                        Caso você tenha concluído um pagamento e esteja em dúvida sobre o andamento do pedido,
                        acompanhe pela sua conta ou entre em contato com nossa equipe informando o código do pedido.
                    </p>

                    <div class="mt-7 flex flex-wrap gap-3">
                        @auth
                            <a href="{{ route('account.index') }}" class="store-button-primary">
                                Ir para Minha conta
                            </a>
                        @endauth

                        <a href="{{ route('contact.index') }}" class="store-button-secondary">
                            Falar com a loja
                        </a>

                        <a href="{{ route('home') }}" class="store-button-secondary">
                            Voltar para o início
                        </a>
                    </div>
                </div>
            @endif

        </div>
    </section>

@endsection
