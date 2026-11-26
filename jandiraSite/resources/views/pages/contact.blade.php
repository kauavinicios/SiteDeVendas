@extends('layouts.app')

@section('title', 'Contato - Butique da Jandira')

@section('content')

    {{-- Cabeçalho --}}
    <section class="border-b border-[var(--color-border)] bg-[var(--color-page-soft)] py-10 md:py-12">
        <div class="store-container">
            <span class="store-eyebrow">Atendimento</span>
            <h1 class="mt-2 text-3xl font-semibold text-[var(--color-text)] md:text-4xl">
                Fale com a Butique da Jandira
            </h1>
            <p class="mt-3 max-w-2xl text-sm leading-relaxed text-[var(--color-text-muted)] md:text-base">
                Tem dúvidas sobre produtos, tamanhos, fragrâncias, prazos de entrega em Campo Grande - MS ou um pedido?
                Envie sua mensagem pelo formulário ou utilize nossos canais diretos.
            </p>
        </div>
    </section>

    {{-- Conteúdo principal --}}
    <section class="py-10 md:py-14">
        <div class="store-container grid gap-8 lg:grid-cols-12">

            {{-- Coluna esquerda: canais e informações --}}
            <div class="space-y-6 lg:col-span-5">
                <div class="rounded-2xl border border-[var(--color-border)] bg-white p-6 shadow-[var(--shadow-sm)] md:p-7">
                    <span class="store-eyebrow">Canais diretos</span>
                    <h2 class="mt-2 text-xl font-semibold text-[var(--color-text)]">
                        Como prefere conversar?
                    </h2>

                    <div class="mt-6 space-y-4 text-sm">
                        <div class="rounded-xl bg-[var(--color-page-soft)] p-4">
                            <p class="text-xs font-bold tracking-wider text-[var(--color-primary)] uppercase">Localização</p>
                            <p class="mt-1 font-medium text-[var(--color-text)]">Campo Grande - MS</p>
                            <p class="mt-0.5 text-[var(--color-text-muted)]">
                                Atendimento regional e consulta de envio sob medida.
                            </p>
                        </div>

                        <div class="rounded-xl bg-[var(--color-page-soft)] p-4">
                            <p class="text-xs font-bold tracking-wider text-[var(--color-primary)] uppercase">Horário de atendimento</p>
                            <p class="mt-1 font-medium text-[var(--color-text)]">Segunda a sábado, em horário comercial</p>
                            <p class="mt-0.5 text-[var(--color-text-muted)]">
                                Respondemos mensagens do site e WhatsApp por ordem de chegada.
                            </p>
                        </div>

                        @if ($supportEmail)
                            <div class="rounded-xl bg-[var(--color-page-soft)] p-4">
                                <p class="text-xs font-bold tracking-wider text-[var(--color-primary)] uppercase">E-mail de contato</p>
                                <a href="mailto:{{ $supportEmail }}" class="mt-1 inline-block font-medium text-[var(--color-primary)] hover:underline">
                                    {{ $supportEmail }}
                                </a>
                            </div>
                        @endif
                    </div>

                    @if ($whatsappUrl)
                        <div class="mt-6 border-t border-[var(--color-border)] pt-6">
                            <p class="text-sm text-[var(--color-text-muted)]">
                                Precisa de resposta rápida sobre disponibilidade ou retirada?
                            </p>
                            <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer"
                                class="store-button-primary mt-3 w-full">
                                Falar pelo WhatsApp
                            </a>
                        </div>
                    @endif

                    <div class="mt-6 border-t border-[var(--color-border)] pt-5">
                        <p class="text-xs font-semibold tracking-wider text-[var(--color-text-muted)] uppercase">
                            Atalhos úteis
                        </p>
                        <div class="mt-3 flex flex-wrap gap-x-5 gap-y-2 text-sm">
                            <a href="{{ route('products.index') }}" class="font-medium text-[var(--color-primary)] hover:underline">
                                Ver catálogo
                            </a>
                            <a href="{{ route('pages.about') }}" class="font-medium text-[var(--color-primary)] hover:underline">
                                Sobre a loja
                            </a>
                            <a href="{{ route('legal.terms') }}" class="font-medium text-[var(--color-primary)] hover:underline">
                                Termos de Uso
                            </a>
                            <a href="{{ route('legal.privacy') }}" class="font-medium text-[var(--color-primary)] hover:underline">
                                Privacidade
                            </a>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-[var(--color-border)] bg-[var(--color-surface-soft)] p-6">
                    <h3 class="text-base font-semibold text-[var(--color-text)]">
                        Produto temporariamente sem estoque?
                    </h3>
                    <p class="mt-2 text-sm leading-relaxed text-[var(--color-text-muted)]">
                        Na página de cada produto esgotado você pode cadastrar seu e-mail no botão
                        <strong class="font-medium text-[var(--color-text)]">“Avise-me quando chegar”</strong>
                        para ser notificado automaticamente assim que houver reposição.
                    </p>
                </div>
            </div>

            {{-- Coluna direita: formulário --}}
            <div class="lg:col-span-7">
                <div class="rounded-2xl border border-[var(--color-border)] bg-white p-6 shadow-[var(--shadow-sm)] md:p-8">
                    <h2 class="text-xl font-semibold text-[var(--color-text)]">
                        Envie sua mensagem
                    </h2>
                    <p class="mt-1 text-sm text-[var(--color-text-muted)]">
                        Preencha os campos abaixo e retornaremos pelo e-mail ou telefone informados.
                    </p>

                    @if (session('contact_success'))
                        <div role="status"
                            class="mt-5 rounded-xl border border-[#c8dfd0] bg-[#eef7f1] px-4 py-3 text-sm font-medium text-[#2f6241]">
                            {{ session('contact_success') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div role="alert"
                            class="mt-5 rounded-xl border border-[#ecc8c8] bg-[#fdf2f2] px-4 py-3 text-sm text-[#9c3232]">
                            <p class="font-semibold">Verifique os campos destacados antes de enviar:</p>
                            <ul class="mt-1.5 list-disc space-y-0.5 pl-5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('contact.store') }}" method="POST" class="mt-6 space-y-5">
                        @csrf

                        <div class="grid gap-5 sm:grid-cols-2">
                            <div>
                                <label for="contact-name" class="block text-sm font-medium text-[var(--color-text)]">
                                    Nome completo <span class="text-[var(--color-primary)]">*</span>
                                </label>
                                <input type="text" id="contact-name" name="name"
                                    value="{{ old('name', $prefill['name']) }}" required maxlength="120"
                                    placeholder="Seu nome"
                                    class="mt-1.5 w-full rounded-xl border border-[var(--color-border)] bg-[#faf8f7] px-4 py-2.5 text-sm text-[var(--color-text)] outline-none transition focus:border-[var(--color-primary-light)] focus:bg-white">
                                @error('name')
                                    <p class="mt-1 text-xs text-[var(--color-danger)]">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="contact-email" class="block text-sm font-medium text-[var(--color-text)]">
                                    E-mail <span class="text-[var(--color-primary)]">*</span>
                                </label>
                                <input type="email" id="contact-email" name="email"
                                    value="{{ old('email', $prefill['email']) }}" required maxlength="255"
                                    placeholder="voce@exemplo.com"
                                    class="mt-1.5 w-full rounded-xl border border-[var(--color-border)] bg-[#faf8f7] px-4 py-2.5 text-sm text-[var(--color-text)] outline-none transition focus:border-[var(--color-primary-light)] focus:bg-white">
                                @error('email')
                                    <p class="mt-1 text-xs text-[var(--color-danger)]">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="grid gap-5 sm:grid-cols-2">
                            <div>
                                <label for="contact-phone" class="block text-sm font-medium text-[var(--color-text)]">
                                    WhatsApp ou telefone <span class="text-xs font-normal text-[var(--color-text-muted)]">(opcional)</span>
                                </label>
                                <input type="text" id="contact-phone" name="phone"
                                    value="{{ old('phone', $prefill['phone']) }}" maxlength="30"
                                    placeholder="(67) 99999-9999"
                                    class="mt-1.5 w-full rounded-xl border border-[var(--color-border)] bg-[#faf8f7] px-4 py-2.5 text-sm text-[var(--color-text)] outline-none transition focus:border-[var(--color-primary-light)] focus:bg-white">
                                @error('phone')
                                    <p class="mt-1 text-xs text-[var(--color-danger)]">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="contact-subject" class="block text-sm font-medium text-[var(--color-text)]">
                                    Assunto <span class="text-[var(--color-primary)]">*</span>
                                </label>
                                <input type="text" id="contact-subject" name="subject"
                                    value="{{ old('subject') }}" required maxlength="160"
                                    placeholder="Ex.: Dúvida sobre jogo de cama ou perfume"
                                    class="mt-1.5 w-full rounded-xl border border-[var(--color-border)] bg-[#faf8f7] px-4 py-2.5 text-sm text-[var(--color-text)] outline-none transition focus:border-[var(--color-primary-light)] focus:bg-white">
                                @error('subject')
                                    <p class="mt-1 text-xs text-[var(--color-danger)]">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div>
                            <label for="contact-message" class="block text-sm font-medium text-[var(--color-text)]">
                                Mensagem <span class="text-[var(--color-primary)]">*</span>
                            </label>
                            <textarea id="contact-message" name="message" rows="5" required maxlength="3000"
                                placeholder="Conte como podemos ajudar você..."
                                class="mt-1.5 w-full rounded-xl border border-[var(--color-border)] bg-[#faf8f7] px-4 py-3 text-sm text-[var(--color-text)] outline-none transition focus:border-[var(--color-primary-light)] focus:bg-white">{{ old('message') }}</textarea>
                            @error('message')
                                <p class="mt-1 text-xs text-[var(--color-danger)]">{{ $message }}</p>
                            @enderror
                        </div>

                        <p class="text-xs leading-relaxed text-[var(--color-text-muted)]">
                            Seus dados são utilizados exclusivamente para retornar este atendimento, conforme nossa
                            <a href="{{ route('legal.privacy') }}" class="font-medium text-[var(--color-primary)] underline">Política de Privacidade</a>.
                        </p>

                        <div class="pt-1">
                            <button type="submit" class="store-button-primary">
                                Enviar mensagem
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </section>

@endsection
