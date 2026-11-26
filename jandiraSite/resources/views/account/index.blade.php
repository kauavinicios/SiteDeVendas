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

        </div>
    </section>

@endsection
