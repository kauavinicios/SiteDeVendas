@extends('layouts.app')

@section('title', 'Início - Butique da Jandira')

@section('content')

    {{-- HERO --}}
    <section class="store-hero">
        <div class="store-container grid items-center gap-10 py-12 lg:grid-cols-2 lg:py-16">

            <div class="max-w-xl">
                <span class="store-eyebrow">
                    Casa & Perfumaria
                </span>

                <h1 class="mt-4 text-4xl font-semibold leading-tight md:text-5xl lg:text-6xl">
                    Conforto para sua casa e fragrâncias para seus momentos.
                </h1>

                <p class="mt-5 max-w-lg text-base leading-relaxed text-gray-600 md:text-lg">
                    Lençóis, fronhas, cobre-leitos, cobertas e perfumes escolhidos com carinho
                    para deixar seus momentos ainda mais especiais.
                </p>

                <div class="mt-7 flex flex-wrap gap-3">
                    <a href="#produtos" class="store-button-primary">
                        Ver produtos
                    </a>

                    <a href="#categorias" class="store-button-secondary">
                        Explorar categorias
                    </a>
                </div>
            </div>

            <div class="store-hero-image">
                <div class="text-center">
                    <span class="text-sm text-gray-400">
                        Imagem principal da boutique
                    </span>
                </div>
            </div>

        </div>
    </section>


    {{-- CATEGORIAS --}}
    <section id="categorias" class="store-container py-14">

        <div class="mb-8 text-center">
            <span class="store-eyebrow">
                Categorias
            </span>

            <h2 class="mt-2 text-3xl font-semibold">
                Encontre o que procura
            </h2>

            <p class="mx-auto mt-3 max-w-2xl text-gray-600">
                Produtos para deixar sua casa mais aconchegante e fragrâncias para todos os momentos.
            </p>
        </div>

        <div class="grid gap-6 md:grid-cols-2">

            {{-- Casa --}}
            <a href="#" class="store-category-card group">

                <div>
                    <span class="store-category-label">
                        Casa
                    </span>

                    <h3 class="mt-2 text-2xl font-semibold">
                        Cama & Conforto
                    </h3>

                    <p class="mt-2 max-w-sm text-gray-600">
                        Lençóis, fronhas, jogos de cama, cobre-leitos e cobertas.
                    </p>

                    <span class="mt-5 inline-flex items-center gap-2 font-medium store-category-link">
                        Ver produtos
                        <span aria-hidden="true">→</span>
                    </span>
                </div>

                <div class="store-category-placeholder">
                    Casa
                </div>

            </a>


            {{-- Perfumaria --}}
            <a href="#" class="store-category-card group">

                <div>
                    <span class="store-category-label">
                        Perfumaria
                    </span>

                    <h3 class="mt-2 text-2xl font-semibold">
                        Perfumes & Cuidados
                    </h3>

                    <p class="mt-2 max-w-sm text-gray-600">
                        Produtos O Boticário e perfumes importados selecionados.
                    </p>

                    <span class="mt-5 inline-flex items-center gap-2 font-medium store-category-link">
                        Ver produtos
                        <span aria-hidden="true">→</span>
                    </span>
                </div>

                <div class="store-category-placeholder">
                    Perfumaria
                </div>

            </a>

        </div>

    </section>


    {{-- PRODUTOS --}}
    <section id="produtos" class="store-products-section">

        <div class="store-container py-14">

            <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">

                <div>
                    <span class="store-eyebrow">
                        Nossa vitrine
                    </span>

                    <h2 class="mt-2 text-3xl font-semibold">
                        Produtos em destaque
                    </h2>

                    <p class="mt-2 text-gray-600">
                        Confira alguns dos produtos disponíveis.
                    </p>
                </div>
                <a href="#" class="store-view-all">
                    Ver todos
                    <span aria-hidden="true">→</span>
                </a>
            </div>


            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @forelse ($produtos as $produto)
                    <article class="store-product-card group">
                        <div class="store-product-image">
                            @if ($produto->images->isNotEmpty())

                                <img src="{{ Storage::url($produto->images->first()->path) }}" alt="{{ $produto->name }}"
                                    class="h-full w-full object-cover">

                            @else

                                <span>
                                    Imagem do produto
                                </span>

                            @endif

                        </div>
                        <div class="p-5">
                            <span class="store-product-category">
                                {{ $produto->category->name }}
                            </span>
                            <h3 class="mt-2 line-clamp-2 text-lg font-semibold">
                                {{ $produto->name }}
                            </h3>
                            @php
                                $menorPreco = $produto->variants
                                    ->where('active', true)
                                    ->min('price');
                            @endphp
                            <div class="mt-5 flex items-center justify-between gap-4">
                                @if ($menorPreco !== null)
                                    <div>
                                        @if ($produto->variants->where('active', true)->count() > 1)
                                            <span class="block text-xs text-gray-500">
                                                A partir de
                                            </span>
                                        @endif
                                        <span class="text-xl font-semibold">
                                            R$ {{ number_format($menorPreco, 2, ',', '.') }}
                                        </span>
                                    </div>
                                @else
                                    <span class="text-sm text-gray-500">
                                        Consulte o preço
                                    </span>
                                @endif

                                <button type="button" class="store-product-button">
                                    Ver produto
                                </button>
                            </div>
                        </div>
                    </article>

                @empty

                    <div class="col-span-full rounded-xl bg-white p-10 text-center">
                        <p class="text-gray-500">
                            Nenhum produto disponível no momento.
                        </p>
                    </div>

                @endforelse

            </div>

        </div>

    </section>


    {{-- WHATSAPP --}}
    <section class="store-container py-14">

        <div class="store-contact-banner">

            <div>
                <span class="store-eyebrow">
                    Atendimento personalizado
                </span>

                <h2 class="mt-2 text-2xl font-semibold md:text-3xl">
                    Ficou com alguma dúvida?
                </h2>

                <p class="mt-2 max-w-xl text-gray-600">
                    Fale diretamente pelo WhatsApp para consultar disponibilidade,
                    tamanhos, fragrâncias ou outras informações.
                </p>
            </div>

            <a href="#" class="store-button-primary whitespace-nowrap">
                Falar no WhatsApp
            </a>

        </div>

    </section>

@endsection