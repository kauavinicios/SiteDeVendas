@extends('layouts.app')

@section('title', $product->name . ' - Butique da Jandira')

@push('styles')
    @vite('resources/css/shop/pages/product-show.css')
@endpush

@push('scripts')
    @vite('resources/js/shop/product-show.js')
@endpush

@section('content')

    <section class="store-product-detail">

        <div class="store-container py-10 md:py-14">

            {{-- Voltar --}}
            <div class="mb-6">
                <a href="{{ route('products.index') }}"
                    class="inline-flex items-center gap-2 text-sm text-gray-500 transition hover:text-[var(--store-primary)]">
                    <span>←</span>
                    Voltar para produtos
                </a>
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


            <div class="grid gap-10 lg:grid-cols-2 lg:gap-14">

                {{-- Imagens --}}
                <div>

                    @php
                        $imagemPrincipal = $product->images->first();
                    @endphp

                    <div class="store-product-main-image">
                        @if ($imagemPrincipal)
                            <img id="product-main-image" src="{{ $imagemPrincipal->url }}"
                                alt="{{ $product->name }}" class="h-full w-full object-cover">
                        @else
                            <span>
                                Imagem principal do produto
                            </span>
                        @endif
                    </div>

                    @if ($product->images->count() > 1)

                        <div class="mt-4 grid grid-cols-4 gap-3">

                            @foreach ($product->images as $image)

                                <button type="button"
                                    class="store-product-thumbnail {{ $loop->first ? 'active' : '' }}"
                                    data-image-url="{{ $image->url }}"
                                    data-image-alt="{{ $product->name }} — foto {{ $loop->iteration }}"
                                    aria-label="Ver foto {{ $loop->iteration }} de {{ $product->name }}"
                                    aria-pressed="{{ $loop->first ? 'true' : 'false' }}">
                                    <img src="{{ $image->url }}"
                                        alt="{{ $product->name }} — miniatura {{ $loop->iteration }}"
                                        class="h-full w-full object-cover" loading="lazy">
                                </button>

                            @endforeach

                        </div>

                    @endif

                </div>


                {{-- Informações --}}
                <div>

                    <span class="store-eyebrow">
                        {{ $product->category->name }}
                    </span>

                    <h1 class="mt-3 text-3xl font-semibold leading-tight md:text-4xl">
                        {{ $product->name }}
                    </h1>

                    @if ($product->featured)

                        <span class="store-product-featured">
                            Produto em destaque
                        </span>

                    @endif


                    {{-- Variações --}}
                    <div class="mt-8">

                        <h2 class="text-sm font-semibold">
                            Escolha uma opção
                        </h2>

                        @if ($product->variants->isNotEmpty())

                            <div class="mt-3 flex flex-wrap gap-3">

                                @foreach ($product->variants as $variant)

                                    <button type="button" class="store-variant-option {{ $loop->first ? 'active' : '' }}"
                                        data-variant-id="{{ $variant->id }}" 
                                        data-price-formatted="R$ {{ number_format($variant->price, 2, ',', '.') }}" 
                                        data-stock="{{ $variant->stock }}">
                                        {{ $variant->name }}
                                    </button>

                                @endforeach

                            </div>

                        @else

                            <p class="mt-2 text-sm text-gray-500">
                                Nenhuma opção disponível no momento.
                            </p>

                        @endif

                    </div>

                    {{-- Preço --}}
                    @if ($product->variants->isNotEmpty())

                        @php
                            $primeiraVariacao = $product->variants->first();
                        @endphp

                        <div class="mt-8">

                            @if ($product->variants->count() > 1)
                                <span id="product-price-label" class="block text-sm text-gray-500">
                                    A partir de
                                </span>
                            @endif

                            <span id="product-price" class="store-product-detail-price">
                                R$ {{ number_format($primeiraVariacao->price, 2, ',', '.') }}
                            </span>

                        </div>

                    @endif

                    {{-- Estoque --}}
                    <div class="mt-4">

                        @if ($product->variants->isNotEmpty())

                            @if ($primeiraVariacao->stock > 0)

                                <span id="product-stock" class="store-stock-available">
                                    Disponível — {{ $primeiraVariacao->stock }}
                                    {{ $primeiraVariacao->stock === 1 ? 'unidade' : 'unidades' }}
                                </span>

                            @else

                                <span id="product-stock" class="store-stock-unavailable">
                                    Indisponível
                                </span>

                            @endif

                        @endif

                    </div>

                    {{-- Ação --}}
                    <div class="mt-6 flex flex-wrap items-center gap-3">
                        <form action="{{ route('cart.store') }}" method="POST" id="cart-form">
                            @csrf
                            <input
                                type="hidden"
                                name="variant_id"
                                id="cart-variant-id"
                                value="{{ $primeiraVariacao?->id }}"
                            >

                            <input
                                type="hidden"
                                name="quantity"
                                value="1"
                            >

                            <button
                                type="submit"
                                id="interest-button"
                                class="store-button-primary"
                                {{ !$primeiraVariacao || $primeiraVariacao->stock <= 0 ? 'disabled' : '' }}
                            >
                                Adicionar ao carrinho
                            </button>
                        </form>

                        @auth
                            <form action="{{ route('favorites.toggle', $product) }}" method="POST">
                                @csrf
                                <button
                                    type="submit"
                                    class="store-button-secondary {{ $isFavorited ? '!bg-[var(--color-surface-soft)]' : '' }}"
                                >
                                    {{ $isFavorited ? '♥ Nos meus preferidos' : '♡ Favoritar' }}
                                </button>
                            </form>
                        @else
                            <a href="{{ route('login') }}" class="store-button-secondary">
                                ♡ Favoritar
                            </a>
                        @endauth
                    </div>

                    {{-- Alerta de reposição de estoque ("Avise-me") --}}
                    @if ($product->variants->isNotEmpty())
                        <div
                            id="stock-alert-box"
                            class="mt-6 rounded-2xl border border-[var(--color-border)] bg-[var(--color-page-soft)] p-5 {{ $primeiraVariacao && $primeiraVariacao->stock <= 0 ? '' : 'hidden' }}"
                        >
                            <h3 class="text-sm font-semibold text-[var(--color-text)]">
                                Avise-me quando voltar ao estoque
                            </h3>
                            <p class="mt-1 text-xs leading-relaxed text-[var(--color-text-muted)]">
                                Informe seu e-mail para receber um aviso automático assim que esta opção for reposta.
                            </p>

                            <form action="{{ route('stock-alerts.store') }}" method="POST" class="mt-3 flex flex-col gap-2 sm:flex-row">
                                @csrf
                                <input
                                    type="hidden"
                                    name="variant_id"
                                    id="alert-variant-id"
                                    value="{{ $primeiraVariacao?->id }}"
                                >

                                <input
                                    type="email"
                                    name="email"
                                    value="{{ old('email', auth()->user()?->email) }}"
                                    required
                                    placeholder="seu@email.com"
                                    class="h-10 flex-1 rounded-xl border border-[var(--color-border)] bg-white px-3.5 text-sm text-[var(--color-text)] outline-none focus:border-[var(--color-primary)]"
                                >

                                <button type="submit" class="store-button-primary whitespace-nowrap">
                                    Avise-me por e-mail
                                </button>
                            </form>

                            @error('email')
                                <p class="mt-2 text-xs text-[var(--color-danger)]">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif


                    {{-- Descrição --}}
                    @if ($product->description)

                        <div class="mt-10 border-t border-gray-200 pt-8">

                            <h2 class="text-lg font-semibold">
                                Sobre o produto
                            </h2>

                            <p class="mt-3 leading-relaxed text-gray-600">
                                {{ $product->description }}
                            </p>

                        </div>

                    @endif

                </div>

            </div>

        </div>

    </section>

@endsection