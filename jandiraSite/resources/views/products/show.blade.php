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


            <div class="grid gap-10 lg:grid-cols-2 lg:gap-14">

                {{-- Imagens --}}
                <div>

                    <div class="store-product-main-image">
                        <span>
                            Imagem principal do produto
                        </span>
                    </div>

                    @if ($product->images->count() > 1)

                        <div class="mt-4 grid grid-cols-4 gap-3">

                            @foreach ($product->images as $image)

                                <div class="store-product-thumbnail">
                                    <span>
                                        {{ $loop->iteration }}
                                    </span>
                                </div>

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