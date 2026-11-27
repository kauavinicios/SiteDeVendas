@extends('layouts.app')

@section('title', 'Minha conta - Butique da Jandira')

@section('content')

    <section class="py-10 md:py-14">
        <div class="store-container max-w-4xl">

            <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <span class="store-eyebrow">Área do cliente</span>
                    <h1 class="mt-2 text-3xl font-semibold text-[var(--color-text)]">
                        Olá, {{ $user->name }}
                    </h1>
                    <p class="mt-1 text-sm text-[var(--color-text-muted)]">
                        Gerencie seus dados pessoais, senha e preferência de recebimento de novidades.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    @if ($user->isStaff())
                        <a href="{{ route('dashboard') }}" class="store-button-primary">
                            Acessar Painel Admin
                        </a>
                    @endif

                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit"
                            class="inline-flex h-11 items-center justify-center rounded-xl border border-[var(--color-border)] bg-white px-5 text-sm font-medium text-[var(--color-text)] transition hover:border-[var(--color-primary)] hover:text-[var(--color-primary)]">
                            Sair da conta
                        </button>
                    </form>
                </div>
            </div>

            @if (session('success'))
                <div class="mb-6 rounded-xl border border-[#cfe0d2] bg-[#f2f8f3] px-4 py-3 text-sm font-medium text-[var(--color-success)]">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-[var(--color-danger)]">
                    {{ session('error') }}
                </div>
            @endif

            @if (session('status') === 'verification-link-sent')
                <div class="mb-6 rounded-xl border border-[#cfe0d2] bg-[#f2f8f3] px-4 py-3 text-sm font-medium text-[var(--color-success)]">
                    Enviamos um novo link de confirmação para o seu e-mail. Confira sua caixa de entrada e spam.
                </div>
            @endif

            @if (! $user->hasVerifiedEmail())
                <div class="mb-6 flex flex-col gap-3 rounded-2xl border border-amber-200 bg-amber-50/80 p-5 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-amber-900">
                            Confirme seu endereço de e-mail
                        </h2>
                        <p class="mt-1 text-xs leading-relaxed text-amber-800">
                            Seu e-mail ainda não foi verificado. Confirmá-lo garante a segurança da sua conta e libera o recebimento de novidades da loja.
                        </p>
                    </div>

                    <form action="{{ route('verification.send') }}" method="POST" class="shrink-0">
                        @csrf
                        <button type="submit"
                            class="inline-flex h-10 items-center justify-center rounded-xl border border-amber-300 bg-white px-4 text-xs font-semibold text-amber-900 transition hover:bg-amber-100">
                            Reenviar link de confirmação
                        </button>
                    </form>
                </div>
            @endif

            <div class="grid gap-6 md:grid-cols-2">

                {{-- Dados pessoais e novidades --}}
                <div class="rounded-2xl border border-[var(--color-border)] bg-white p-6 shadow-[var(--shadow-sm)]">
                    <h2 class="text-lg font-semibold text-[var(--color-text)]">
                        Meus dados e novidades
                    </h2>
                    <p class="mt-1 text-sm text-[var(--color-text-muted)]">
                        Mantenha seu contato atualizado e escolha se deseja receber e-mails promocionais.
                    </p>

                    <form action="{{ route('account.profile.update') }}" method="POST" class="mt-6 space-y-4">
                        @csrf
                        @method('PATCH')

                        <div>
                            <label for="account-name" class="mb-1.5 block text-sm font-medium text-[var(--color-text)]">
                                Nome completo
                            </label>
                            <input id="account-name" type="text" name="name" value="{{ old('name', $user->name) }}" required
                                class="h-11 w-full rounded-xl border border-[var(--color-border)] bg-[var(--color-background)] px-3.5 text-sm text-[var(--color-text)] outline-none focus:border-[var(--color-primary)]">
                            @error('name')
                                <p class="mt-1 text-xs text-[var(--color-danger)]">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <div class="mb-1.5 flex items-center justify-between gap-2">
                                <label for="account-email" class="block text-sm font-medium text-[var(--color-text)]">
                                    E-mail
                                </label>

                                @if ($user->hasVerifiedEmail())
                                    <span class="inline-flex items-center rounded-full bg-[#f2f8f3] px-2.5 py-0.5 text-[11px] font-medium text-[var(--color-success)]">
                                        Verificado
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-0.5 text-[11px] font-medium text-amber-800">
                                        Aguardando confirmação
                                    </span>
                                @endif
                            </div>
                            <input id="account-email" type="email" name="email" value="{{ old('email', $user->email) }}" required
                                class="h-11 w-full rounded-xl border border-[var(--color-border)] bg-[var(--color-background)] px-3.5 text-sm text-[var(--color-text)] outline-none focus:border-[var(--color-primary)]">
                            @error('email')
                                <p class="mt-1 text-xs text-[var(--color-danger)]">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="account-phone" class="mb-1.5 block text-sm font-medium text-[var(--color-text)]">
                                Telefone / WhatsApp
                            </label>
                            <input id="account-phone" type="text" name="phone" value="{{ old('phone', $user->phone) }}"
                                placeholder="(67) 99999-9999"
                                class="h-11 w-full rounded-xl border border-[var(--color-border)] bg-[var(--color-background)] px-3.5 text-sm text-[var(--color-text)] outline-none focus:border-[var(--color-primary)]">
                            @error('phone')
                                <p class="mt-1 text-xs text-[var(--color-danger)]">{{ $message }}</p>
                            @enderror
                        </div>

                        <label for="account-opt-in"
                            class="flex cursor-pointer items-start gap-3 rounded-xl border border-[var(--color-border)] bg-[var(--color-page-soft)] p-3.5">
                            <input id="account-opt-in" type="checkbox" name="marketing_opt_in" value="1"
                                @checked(old('marketing_opt_in', $user->marketing_opt_in))
                                class="mt-0.5 size-4 accent-[var(--color-primary)]">

                            <div class="text-xs leading-relaxed text-[var(--color-text-muted)]">
                                <span class="block font-medium text-[var(--color-text)]">
                                    Receber novidades por e-mail
                                </span>
                                Quero receber lançamentos, reposições e ofertas exclusivas da Butique da Jandira.
                                @if ($user->marketing_opt_in && $user->marketing_opt_in_at)
                                    <span class="mt-1 block text-[11px]">
                                        Inscrito desde {{ $user->marketing_opt_in_at->format('d/m/Y') }}.
                                    </span>
                                @endif
                            </div>
                        </label>

                        <button type="submit" class="store-button-primary w-full">
                            Salvar dados
                        </button>

                        @if ($user->terms_accepted_at)
                            <p class="text-[11px] leading-relaxed text-[var(--color-text-muted)]">
                                Você aceitou os
                                <a href="{{ route('legal.terms') }}" target="_blank" rel="noopener noreferrer" class="font-medium text-[var(--color-primary)] underline">
                                    Termos de Uso
                                </a>
                                (v{{ $user->terms_version ?? '1.0' }}) e a
                                <a href="{{ route('legal.privacy') }}" target="_blank" rel="noopener noreferrer" class="font-medium text-[var(--color-primary)] underline">
                                    Política de Privacidade
                                </a>
                                (v{{ $user->privacy_version ?? '1.0' }}) em {{ $user->terms_accepted_at->format('d/m/Y H:i') }}.
                            </p>
                        @endif
                    </form>
                </div>

                {{-- Alterar senha --}}
                <div class="rounded-2xl border border-[var(--color-border)] bg-white p-6 shadow-[var(--shadow-sm)]">
                    <h2 class="text-lg font-semibold text-[var(--color-text)]">
                        Segurança da conta
                    </h2>
                    <p class="mt-1 text-sm text-[var(--color-text-muted)]">
                        Altere sua senha regularmente para manter sua conta protegida.
                    </p>

                    <form action="{{ route('account.password.update') }}" method="POST" class="mt-6 space-y-4">
                        @csrf
                        @method('PUT')

                        <div>
                            <label for="current-password" class="mb-1.5 block text-sm font-medium text-[var(--color-text)]">
                                Senha atual
                            </label>
                            <input id="current-password" type="password" name="current_password" required
                                class="h-11 w-full rounded-xl border border-[var(--color-border)] bg-[var(--color-background)] px-3.5 text-sm text-[var(--color-text)] outline-none focus:border-[var(--color-primary)]">
                            @error('current_password', 'passwordUpdate')
                                <p class="mt-1 text-xs text-[var(--color-danger)]">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="new-password" class="mb-1.5 block text-sm font-medium text-[var(--color-text)]">
                                Nova senha
                            </label>
                            <input id="new-password" type="password" name="password" required
                                class="h-11 w-full rounded-xl border border-[var(--color-border)] bg-[var(--color-background)] px-3.5 text-sm text-[var(--color-text)] outline-none focus:border-[var(--color-primary)]">
                            @error('password', 'passwordUpdate')
                                <p class="mt-1 text-xs text-[var(--color-danger)]">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="new-password-confirmation" class="mb-1.5 block text-sm font-medium text-[var(--color-text)]">
                                Confirmar nova senha
                            </label>
                            <input id="new-password-confirmation" type="password" name="password_confirmation" required
                                class="h-11 w-full rounded-xl border border-[var(--color-border)] bg-[var(--color-background)] px-3.5 text-sm text-[var(--color-text)] outline-none focus:border-[var(--color-primary)]">
                        </div>

                        <button type="submit" class="store-button-primary w-full">
                            Alterar senha
                        </button>
                    </form>
                </div>

            </div>

            {{-- Meus preferidos --}}
            <div class="mt-6 rounded-2xl border border-[var(--color-border)] bg-white p-6 shadow-[var(--shadow-sm)]">
                <div>
                    <h2 class="text-lg font-semibold text-[var(--color-text)]">
                        Meus preferidos
                    </h2>
                    <p class="text-sm text-[var(--color-text-muted)]">
                        Produtos salvos por você. Quando um item esgotado voltar ao estoque, enviaremos um aviso por e-mail.
                    </p>
                </div>

                @if ($favorites->isNotEmpty())
                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        @foreach ($favorites as $favorite)
                            @php
                                $favProduct = $favorite->product;
                                $activeVariants = $favProduct->variants->where('active', true);
                                $minPrice = $activeVariants->min('price');
                                $hasStock = $activeVariants->sum('stock') > 0;
                                $thumbUrl = $favProduct->images->first()?->url;
                            @endphp

                            <article class="flex items-center gap-4 rounded-xl border border-[var(--color-border)] bg-[var(--color-background)] p-4">
                                <div class="size-16 shrink-0 overflow-hidden rounded-xl border border-[var(--color-border)] bg-[var(--color-surface-soft)]">
                                    @if ($thumbUrl)
                                        <img src="{{ $thumbUrl }}" alt="{{ $favProduct->name }}" class="h-full w-full object-cover" loading="lazy">
                                    @endif
                                </div>

                                <div class="min-w-0 flex-1">
                                    <a href="{{ route('products.show', $favProduct) }}" class="block truncate text-sm font-semibold text-[var(--color-text)] hover:text-[var(--color-primary)]">
                                        {{ $favProduct->name }}
                                    </a>

                                    <p class="mt-0.5 text-xs text-[var(--color-text-muted)]">
                                        {{ $minPrice !== null ? 'A partir de R$ '.number_format((float) $minPrice, 2, ',', '.') : 'Consulte o preço' }}
                                    </p>

                                    <div class="mt-2 flex flex-wrap items-center gap-2">
                                        @if ($hasStock)
                                            <span class="inline-flex rounded-full bg-green-50 px-2 py-0.5 text-[11px] font-medium text-green-700">
                                                Disponível
                                            </span>
                                        @else
                                            <span class="inline-flex rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-medium text-amber-800">
                                                Esgotado (aviso ativo)
                                            </span>
                                        @endif

                                        <form action="{{ route('favorites.toggle', $favProduct) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="text-[11px] font-medium text-[var(--color-danger)] hover:underline">
                                                Remover
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @else
                    <p class="mt-5 rounded-xl border border-dashed border-[var(--color-border)] bg-[var(--color-page-soft)] px-4 py-6 text-center text-sm text-[var(--color-text-muted)]">
                        Você ainda não salvou produtos nos preferidos. Clique em <strong>Favoritar</strong> na página de qualquer produto!
                    </p>
                @endif
            </div>

            {{-- Minhas compras e estatísticas --}}
            <div class="mt-6 rounded-2xl border border-[var(--color-border)] bg-white p-6 shadow-[var(--shadow-sm)]">
                <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-[var(--color-text)]">
                            Minhas compras e estatísticas
                        </h2>
                        <p class="text-sm text-[var(--color-text-muted)]">
                            Acompanhe o status dos pedidos feitos na loja e seu resumo de compras confirmadas.
                        </p>
                    </div>
                </div>

                <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-xl border border-[var(--color-border)] bg-[var(--color-page-soft)] p-4">
                        <p class="text-xs font-medium text-[var(--color-text-muted)]">Pedidos realizados</p>
                        <p class="mt-1 text-xl font-semibold text-[var(--color-text)]">{{ $orderStats['total_orders'] }}</p>
                    </div>

                    <div class="rounded-xl border border-[var(--color-border)] bg-[var(--color-page-soft)] p-4">
                        <p class="text-xs font-medium text-[var(--color-text-muted)]">Pedidos confirmados</p>
                        <p class="mt-1 text-xl font-semibold text-[var(--color-text)]">{{ $orderStats['confirmed_orders'] }}</p>
                    </div>

                    <div class="rounded-xl border border-[var(--color-border)] bg-[var(--color-page-soft)] p-4">
                        <p class="text-xs font-medium text-[var(--color-text-muted)]">Itens adquiridos</p>
                        <p className="mt-1 text-xl font-semibold text-[var(--color-text)]">{{ $orderStats['total_items'] }}</p>
                    </div>

                    <div class="rounded-xl border border-[var(--color-border)] bg-[var(--color-page-soft)] p-4">
                        <p class="text-xs font-medium text-[var(--color-text-muted)]">Total em compras</p>
                        <p class="mt-1 text-xl font-semibold text-[var(--color-primary)]">
                            R$ {{ number_format($orderStats['total_spent'], 2, ',', '.') }}
                        </p>
                    </div>
                </div>

                @if ($orders->isNotEmpty())
                    <div class="mt-6 space-y-4">
                        @foreach ($orders as $order)
                            @php
                                $badgeClasses = match ($order->status->tone()) {
                                    'success' => 'bg-green-50 text-green-700',
                                    'danger' => 'bg-red-50 text-red-700',
                                    'info' => 'bg-blue-50 text-blue-700',
                                    'primary' => 'bg-[var(--color-surface-soft)] text-[var(--color-primary)]',
                                    default => 'bg-amber-50 text-amber-800',
                                };

                                $tx = $order->latestPaymentTransaction;
                                $methodLabel = $tx?->captureMethodLabel() ?? match ($order->payment_method) {
                                    'pix' => 'Pix',
                                    'credit_card' => 'Cartão de crédito',
                                    'debit_card' => 'Cartão de débito',
                                    default => $order->payment_method,
                                };
                            @endphp

                            <article class="rounded-xl border border-[var(--color-border)] bg-[var(--color-background)] p-4">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold text-[var(--color-text)]">
                                            Pedido #{{ $order->code }}
                                        </p>
                                        <p class="text-xs text-[var(--color-text-muted)]">
                                            Realizado em {{ $order->created_at?->format('d/m/Y H:i') }} &bull; {{ $order->items_count }} item(ns)
                                        </p>
                                    </div>

                                    <div class="flex items-center gap-3">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $badgeClasses }}">
                                            {{ $order->status->label() }}
                                        </span>

                                        <strong class="text-sm font-semibold text-[var(--color-text)]">
                                            R$ {{ number_format((float) $order->total, 2, ',', '.') }}
                                        </strong>
                                    </div>
                                </div>

                                @if ($tx || $methodLabel)
                                    <div class="mt-3 flex flex-wrap items-center justify-between gap-2 rounded-lg bg-[var(--color-page-soft)] px-3 py-2 text-xs text-[var(--color-text-muted)]">
                                        <div>
                                            @if ($methodLabel)
                                                <span>
                                                    Pagamento: <strong class="text-[var(--color-text)]">{{ $methodLabel }}</strong>
                                                    @if ($tx?->installments && $tx->installments > 1)
                                                        <span>({{ $tx->installments }}x)</span>
                                                    @endif
                                                </span>
                                            @else
                                                <span>
                                                    Pagamento: <strong class="text-[var(--color-text)]">Aguardando pagamento</strong>
                                                </span>
                                            @endif

                                            @if ($tx?->paid_at)
                                                <span class="ml-2 text-[var(--color-success)]">
                                                    &bull; Pago em {{ $tx->paid_at->format('d/m/Y H:i') }}
                                                </span>
                                            @endif
                                        </div>

                                        <div class="flex flex-wrap items-center gap-3">
                                            @if ($tx && $tx->isPaid() && $tx->receipt_url)
                                                <a href="{{ $tx->receipt_url }}" target="_blank" rel="noopener noreferrer"
                                                    class="font-semibold text-[var(--color-primary)] hover:underline">
                                                    Ver comprovante oficial
                                                </a>
                                            @elseif ($tx && ! $tx->isPaid() && $order->status === \App\Enums\OrderStatus::Pending && $tx->checkout_url)
                                                <a href="{{ $tx->checkout_url }}"
                                                    class="font-semibold text-[var(--color-primary)] hover:underline">
                                                    Retomar pagamento na InfinitePay
                                                </a>

                                                <a href="{{ route('checkout.return', ['order_nsu' => $tx->order_nsu, 'slug' => $tx->slug]) }}"
                                                    class="font-medium text-[var(--color-text-muted)] hover:text-[var(--color-text)] hover:underline">
                                                    Verificar status
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                @endif

                                <ul class="mt-3 space-y-1 border-t border-[var(--color-border)] pt-3 text-xs text-[var(--color-text-muted)]">
                                    @foreach ($order->items as $item)
                                        <li class="flex justify-between gap-4">
                                            <span>{{ $item->quantity }}x {{ $item->product_name }} ({{ $item->variant_name }})</span>
                                            <span>R$ {{ number_format((float) $item->subtotal, 2, ',', '.') }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </article>
                        @endforeach
                    </div>
                @else
                    <p class="mt-5 rounded-xl border border-dashed border-[var(--color-border)] bg-[var(--color-page-soft)] px-4 py-6 text-center text-sm text-[var(--color-text-muted)]">
                        Você ainda não realizou nenhum pedido.
                    </p>
                @endif
            </div>

            {{-- Privacidade e encerramento da conta (LGPD) --}}
            <div class="mt-6 rounded-2xl border border-[var(--color-border)] bg-white p-6 shadow-[var(--shadow-sm)]">
                @if ($user->isCustomer())
                    <h2 class="text-lg font-semibold text-[var(--color-danger)]">
                        Privacidade e exclusão de conta
                    </h2>
                    <p class="mt-1 text-sm leading-relaxed text-[var(--color-text-muted)]">
                        Conforme a LGPD, você pode encerrar sua conta a qualquer momento. Contas sem pedidos são removidas definitivamente;
                        caso exista histórico de compras, seus dados pessoais de cadastro são anonimizados e preservamos apenas os registros comerciais exigidos por lei
                        (<a href="{{ route('legal.privacy') }}" class="font-medium text-[var(--color-primary)] underline">saiba mais</a>).
                    </p>

                    <form action="{{ route('account.destroy') }}" method="POST"
                        onsubmit="return confirm('Tem certeza que deseja excluir sua conta permanentemente? Esta ação não pode ser desfeita.');"
                        class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-end">
                        @csrf
                        @method('DELETE')

                        <div class="flex-1">
                            <label for="delete-account-password" class="mb-1.5 block text-sm font-medium text-[var(--color-text)]">
                                Confirme sua senha atual
                            </label>
                            <input id="delete-account-password" type="password" name="password" required
                                placeholder="Digite sua senha para confirmar"
                                class="h-11 w-full rounded-xl border border-[var(--color-border)] bg-[var(--color-background)] px-3.5 text-sm text-[var(--color-text)] outline-none focus:border-[var(--color-danger)]">
                            @error('password', 'accountDeletion')
                                <p class="mt-1 text-xs text-[var(--color-danger)]">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="submit"
                            class="inline-flex h-11 shrink-0 items-center justify-center rounded-xl border border-red-200 bg-red-50 px-5 text-sm font-semibold text-[var(--color-danger)] transition hover:bg-red-100">
                            Excluir minha conta permanentemente
                        </button>
                    </form>
                @else
                    <h2 class="text-base font-semibold text-[var(--color-text)]">
                        Conta interna da equipe
                    </h2>
                    <p class="mt-1 text-sm text-[var(--color-text-muted)]">
                        Para manter a segurança e a rastreabilidade das operações da loja, contas internas da equipe não podem ser excluídas, apenas desativadas por um administrador em <a href="{{ route('admin.users.index') }}" class="font-medium text-[var(--color-primary)] underline">Usuários</a>.
                    </p>
                @endif
            </div>

        </div>
    </section>

@endsection
