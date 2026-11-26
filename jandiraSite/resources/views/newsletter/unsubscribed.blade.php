@extends('layouts.app')

@section('title', 'Inscrição cancelada - Butique da Jandira')

@section('content')

    <section class="py-12 md:py-16">
        <div class="store-container max-w-xl">

            <div class="rounded-2xl border border-[var(--color-border)] bg-white p-8 text-center shadow-[var(--shadow-sm)]">
                <span class="store-eyebrow">Preferências de e-mail (LGPD)</span>

                <h1 class="mt-3 text-2xl font-semibold text-[var(--color-text)] md:text-3xl">
                    Você não receberá mais e-mails de novidades
                </h1>

                <p class="mt-3 text-sm leading-relaxed text-[var(--color-text-muted)]">
                    Confirmamos o cancelamento da inscrição para o endereço <strong>{{ $user->email }}</strong>.
                    Caso mude de ideia no futuro, você pode reativar o recebimento a qualquer momento em <strong>Minha conta</strong>.
                </p>

                <div class="mt-7 flex flex-wrap items-center justify-center gap-3">
                    <a href="{{ route('home') }}" class="store-button-primary">
                        Voltar para a loja
                    </a>

                    @auth
                        <a href="{{ route('account.index') }}" class="store-button-secondary">
                            Ir para Minha conta
                        </a>
                    @endauth
                </div>
            </div>

        </div>
    </section>

@endsection
