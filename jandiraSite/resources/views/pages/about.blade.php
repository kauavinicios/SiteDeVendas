@extends('layouts.app')

@section('title', 'Sobre nós - Butique da Jandira')

@section('content')

    {{-- Hero institucional --}}
    <section class="border-b border-[var(--color-border)] bg-[radial-gradient(circle_at_top_right,#f7efec_0%,#fffdfb_62%)] py-12 md:py-16">
        <div class="store-container grid items-center gap-10 lg:grid-cols-12">
            <div class="lg:col-span-7">
                <span class="store-eyebrow">Nossa história</span>

                <h1 class="mt-3 text-3xl font-semibold leading-tight text-[var(--color-text)] md:text-4xl lg:text-5xl">
                    Cuidado em cada detalhe para vestir sua casa e perfumar seus momentos.
                </h1>

                <p class="mt-5 max-w-2xl text-base leading-relaxed text-[var(--color-text-muted)] md:text-lg">
                    A <strong class="font-semibold text-[var(--color-text)]">Butique da Jandira — Casa &amp; Perfumaria</strong> nasceu em
                    Campo Grande - MS com o propósito de reunir enxovais aconchegantes e fragrâncias escolhidas a dedo,
                    sempre com atendimento próximo, transparente e humano.
                </p>

                <div class="mt-7 flex flex-wrap gap-3">
                    <a href="{{ route('products.index') }}" class="store-button-primary">
                        Explorar catálogo
                    </a>

                    <a href="{{ route('contact.index') }}" class="store-button-secondary">
                        Falar com a equipe
                    </a>
                </div>
            </div>

            <div class="lg:col-span-5">
                <div class="rounded-3xl border border-[var(--color-border)] bg-white p-7 shadow-[var(--shadow-md)] md:p-8">
                    <div class="flex items-center gap-4 border-b border-[var(--color-border)] pb-6">
                        <div class="store-logo-symbol">J</div>
                        <div>
                            <p class="font-serif text-xl font-semibold text-[var(--color-primary-dark)]">
                                Butique da Jandira
                            </p>
                            <p class="text-xs font-semibold tracking-widest text-[var(--color-text-muted)] uppercase">
                                Casa &amp; Perfumaria
                            </p>
                        </div>
                    </div>

                    <dl class="mt-6 space-y-4 text-sm">
                        <div class="rounded-2xl bg-[var(--color-page-soft)] p-4">
                            <dt class="font-semibold text-[var(--color-text)]">Atendimento local e dedicado</dt>
                            <dd class="mt-1 text-[var(--color-text-muted)]">
                                Baseados em Campo Grande - MS, acompanhamos cada pedido do catálogo à entrega.
                            </dd>
                        </div>

                        <div class="rounded-2xl bg-[var(--color-page-soft)] p-4">
                            <dt class="font-semibold text-[var(--color-text)]">Curadoria com propósito</dt>
                            <dd class="mt-1 text-[var(--color-text-muted)]">
                                Selecionamos peças de cama macias ao toque e perfumes marcantes para o dia a dia ou para presentear.
                            </dd>
                        </div>

                        <div class="rounded-2xl bg-[var(--color-page-soft)] p-4">
                            <dt class="font-semibold text-[var(--color-text)]">Transparência com seus dados</dt>
                            <dd class="mt-1 text-[var(--color-text-muted)]">
                                Respeitamos sua privacidade em todas as etapas, em conformidade com a LGPD.
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </section>

    {{-- Nossa essência --}}
    <section class="py-14">
        <div class="store-container grid gap-8 lg:grid-cols-12 lg:items-center">
            <div class="lg:col-span-6">
                <span class="store-eyebrow">Nossa essência</span>

                <h2 class="mt-2 text-2xl font-semibold text-[var(--color-text)] md:text-3xl">
                    Uma vitrine pensada para quem valoriza conforto e presença.
                </h2>

                <div class="mt-4 space-y-4 text-sm leading-relaxed text-[var(--color-text-muted)] md:text-base">
                    <p>
                        Acreditamos que o bem-estar começa dentro de casa: na textura agradável de um jogo de cama novo,
                        no caimento de um cobre-leito bem escolhido e no aroma que acolhe quem chega.
                    </p>
                    <p>
                        Por isso, cada item do nosso catálogo é selecionado considerando durabilidade, acabamento,
                        apresentação para presente e excelente custo-benefício — seja na linha de enxoval ou na perfumaria.
                    </p>
                </div>
            </div>

            <div class="lg:col-span-6">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="rounded-2xl border border-[var(--color-border)] bg-[var(--color-surface-soft)] p-6">
                        <p class="text-xs font-bold tracking-wider text-[var(--color-primary)] uppercase">Linha Casa</p>
                        <p class="mt-2 text-lg font-semibold text-[var(--color-text)]">Cama &amp; Aconchego</p>
                        <p class="mt-2 text-sm leading-relaxed text-[var(--color-text-muted)]">
                            Lençóis, fronhas, cobertas e cobre-leitos em diferentes tamanhos e tonalidades.
                        </p>
                    </div>

                    <div class="rounded-2xl border border-[var(--color-border)] bg-[var(--color-surface-soft)] p-6">
                        <p class="text-xs font-bold tracking-wider text-[var(--color-primary)] uppercase">Linha Perfumaria</p>
                        <p class="mt-2 text-lg font-semibold text-[var(--color-text)]">Fragrâncias Especiais</p>
                        <p class="mt-2 text-sm leading-relaxed text-[var(--color-text-muted)]">
                            Seleção O Boticário, importados e opções para cuidados diários e datas comemorativas.
                        </p>
                    </div>

                    <div class="rounded-2xl border border-[var(--color-border)] bg-white p-6 shadow-[var(--shadow-sm)] sm:col-span-2">
                        <p class="text-xs font-bold tracking-wider text-[var(--color-primary)] uppercase">Experiência de compra</p>
                        <p class="mt-2 text-lg font-semibold text-[var(--color-text)]">
                            Acompanhe disponibilidade e novidades sem complicação
                        </p>
                        <p class="mt-2 text-sm leading-relaxed text-[var(--color-text-muted)]">
                            Salve seus produtos preferidos, receba aviso automático por e-mail quando uma variação voltar ao estoque
                            e acompanhe o histórico dos seus pedidos diretamente em sua conta.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Nossos pilares --}}
    <section class="border-y border-[var(--color-border)] bg-[var(--color-page-soft)] py-14">
        <div class="store-container">
            <div class="mx-auto max-w-2xl text-center">
                <span class="store-eyebrow">Nossos pilares</span>
                <h2 class="mt-2 text-2xl font-semibold text-[var(--color-text)] md:text-3xl">
                    O que guia cada escolha da boutique
                </h2>
                <p class="mt-3 text-sm text-[var(--color-text-muted)] md:text-base">
                    Unimos organização online e atendimento atencioso para tornar sua experiência leve e segura.
                </p>
            </div>

            <div class="mt-10 grid gap-6 md:grid-cols-3">
                <article class="flex flex-col justify-between rounded-2xl border border-[var(--color-border)] bg-white p-6 shadow-[var(--shadow-sm)]">
                    <div>
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-[var(--color-surface-soft)] font-serif text-lg font-semibold text-[var(--color-primary)]">
                            01
                        </span>
                        <h3 class="mt-4 text-xl font-semibold text-[var(--color-text)]">Cama &amp; Conforto</h3>
                        <p class="mt-2 text-sm leading-relaxed text-[var(--color-text-muted)]">
                            Peças pensadas para transformar o quarto em um refúgio acolhedor, com informações claras de tamanho e variação.
                        </p>
                    </div>

                    <div class="mt-5 pt-4 border-t border-[var(--color-border)]">
                        <a href="{{ route('products.index', ['categorias' => ['casa']]) }}" class="store-view-all">
                            Ver itens de Casa
                            <span aria-hidden="true">&rarr;</span>
                        </a>
                    </div>
                </article>

                <article class="flex flex-col justify-between rounded-2xl border border-[var(--color-border)] bg-white p-6 shadow-[var(--shadow-sm)]">
                    <div>
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-[var(--color-surface-soft)] font-serif text-lg font-semibold text-[var(--color-primary)]">
                            02
                        </span>
                        <h3 class="mt-4 text-xl font-semibold text-[var(--color-text)]">Perfumaria Selecionada</h3>
                        <p class="mt-2 text-sm leading-relaxed text-[var(--color-text-muted)]">
                            Fragrâncias originais e queridinhas do público para combinar com cada estação, ocasião e personalidade.
                        </p>
                    </div>

                    <div class="mt-5 pt-4 border-t border-[var(--color-border)]">
                        <a href="{{ route('products.index', ['categorias' => ['perfumaria']]) }}" class="store-view-all">
                            Ver Perfumaria
                            <span aria-hidden="true">&rarr;</span>
                        </a>
                    </div>
                </article>

                <article class="flex flex-col justify-between rounded-2xl border border-[var(--color-border)] bg-white p-6 shadow-[var(--shadow-sm)]">
                    <div>
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-[var(--color-surface-soft)] font-serif text-lg font-semibold text-[var(--color-primary)]">
                            03
                        </span>
                        <h3 class="mt-4 text-xl font-semibold text-[var(--color-text)]">Atendimento Próximo</h3>
                        <p class="mt-2 text-sm leading-relaxed text-[var(--color-text-muted)]">
                            Dúvidas sobre medidas, notas olfativas, presentes ou entregas? Nossa equipe orienta você de forma direta.
                        </p>
                    </div>

                    <div class="mt-5 pt-4 border-t border-[var(--color-border)]">
                        <a href="{{ route('contact.index') }}" class="store-view-all">
                            Entrar em contato
                            <span aria-hidden="true">&rarr;</span>
                        </a>
                    </div>
                </article>
            </div>
        </div>
    </section>

    {{-- Como funciona --}}
    <section class="py-14">
        <div class="store-container">
            <div class="rounded-3xl border border-[var(--color-border)] bg-white p-7 shadow-[var(--shadow-sm)] md:p-10">
                <div class="max-w-2xl">
                    <span class="store-eyebrow">Simples do início ao fim</span>
                    <h2 class="mt-2 text-2xl font-semibold text-[var(--color-text)] md:text-3xl">
                        Como comprar na Butique da Jandira
                    </h2>
                </div>

                <div class="mt-8 grid gap-6 md:grid-cols-3">
                    <div class="rounded-2xl bg-[var(--color-page-soft)] p-5">
                        <span class="text-xs font-bold tracking-wider text-[var(--color-primary)] uppercase">Passo 1</span>
                        <h3 class="mt-2 text-base font-semibold text-[var(--color-text)]">Escolha suas variações</h3>
                        <p class="mt-1.5 text-sm leading-relaxed text-[var(--color-text-muted)]">
                            Navegue pelas categorias, confira fotos reais e selecione o tamanho ou fragrância ideal.
                        </p>
                    </div>

                    <div class="rounded-2xl bg-[var(--color-page-soft)] p-5">
                        <span class="text-xs font-bold tracking-wider text-[var(--color-primary)] uppercase">Passo 2</span>
                        <h3 class="mt-2 text-base font-semibold text-[var(--color-text)]">Monte seu pedido</h3>
                        <p class="mt-1.5 text-sm leading-relaxed text-[var(--color-text-muted)]">
                            Adicione os itens ao carrinho e acompanhe tudo com clareza, incluindo histórico na sua conta.
                        </p>
                    </div>

                    <div class="rounded-2xl bg-[var(--color-page-soft)] p-5">
                        <span class="text-xs font-bold tracking-wider text-[var(--color-primary)] uppercase">Passo 3</span>
                        <h3 class="mt-2 text-base font-semibold text-[var(--color-text)]">Combine entrega e pagamento</h3>
                        <p class="mt-1.5 text-sm leading-relaxed text-[var(--color-text-muted)]">
                            Receba atendimento atencioso para confirmar entrega em Campo Grande - MS e concluir sua compra com segurança.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- CTA final --}}
    <section class="pb-14">
        <div class="store-container">
            <div class="flex flex-col justify-between gap-6 rounded-3xl border border-[var(--color-border)] bg-[var(--color-surface-soft)] p-7 md:flex-row md:items-center md:p-10">
                <div class="max-w-xl">
                    <span class="store-eyebrow">Estamos por aqui</span>
                    <h2 class="mt-2 text-2xl font-semibold text-[var(--color-text)] md:text-3xl">
                        Quer ajuda para escolher um presente ou item para sua casa?
                    </h2>
                    <p class="mt-2 text-sm text-[var(--color-text-muted)] md:text-base">
                        Visite nossa vitrine completa ou envie uma mensagem diretamente para nossa equipe.
                    </p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('contact.index') }}" class="store-button-primary">
                        Fale conosco
                    </a>

                    @if ($whatsappUrl)
                        <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer" class="store-button-secondary">
                            Chamar no WhatsApp
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </section>

@endsection
