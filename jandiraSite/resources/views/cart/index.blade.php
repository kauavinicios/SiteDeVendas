@extends('layouts.app')

@section('title', 'Carrinho - Butique da Jandira')

@push('styles')
    @vite('resources/css/shop/pages/cart.css')
@endpush

@push('scripts')
    @vite('resources/js/shop/cart.js')
@endpush

@section('content')

    <section class="store-cart-page">

        <div class="store-container py-10 md:py-14">

            <div class="mb-8">

                <span class="store-eyebrow">
                    Seu pedido
                </span>

                <h1 class="mt-2 text-3xl font-semibold md:text-4xl">
                    Carrinho
                </h1>

            </div>


            {{-- Mensagens do carrinho --}}
            @php
                $cartMessage = session('success') ?? session('error');
                $cartMessageType = session('error') ? 'error' : 'success';
            @endphp

            <div
                class="mb-6 rounded-xl border p-4 text-sm
                    {{ $cartMessage ? '' : 'hidden' }}
                    {{ $cartMessageType === 'success'
                        ? 'border-green-200 bg-green-50 text-green-700'
                        : 'border-red-200 bg-red-50 text-red-700'
                    }}"
                data-cart-message
                data-message-type="{{ $cartMessageType }}"
            >
                {{ $cartMessage }}
            </div>

            @if (!empty($cart))

                <div class="grid gap-8 lg:grid-cols-[1fr_340px]" data-cart-content>

                    {{-- Produtos --}}
                    <div class="flex flex-col gap-4">

                        @foreach ($cart as $item)

                            <article
                                class="store-cart-item"
                                data-cart-item
                                data-variant-id="{{ $item['variant_id'] }}"
                                data-product-name="{{ $item['product_name'] }}"
                                data-variant-name="{{ $item['variant_name'] }}"
                                data-unit-price="{{ $item['price'] }}"
                                data-update-url="{{ route('cart.update', $item['variant_id']) }}"
                                data-delete-url="{{ route('cart.destroy', $item['variant_id']) }}"
                            >

                                {{-- Imagem temporária --}}
                                <div class="store-cart-image">
                                    <span>
                                        Imagem
                                    </span>
                                </div>


                                {{-- Informações --}}
                                <div class="min-w-0 flex-1">

                                    <a
                                        href="{{ route('products.show', $item['product_slug']) }}"
                                        class="store-cart-product-name"
                                    >
                                        {{ $item['product_name'] }}
                                    </a>

                                    <p class="mt-1 text-sm text-gray-500">
                                        {{ $item['variant_name'] }}
                                    </p>

                                    <div class="mt-4 flex flex-wrap items-center gap-5">

                                        {{-- Quantidade --}}
                                        <div class="store-quantity-form">

                                            <button
                                                type="button"
                                                class="store-quantity-button"
                                                data-action="decrease"
                                                aria-label="Diminuir quantidade"
                                            >
                                                −
                                            </button>

                                            <input
                                                type="number"
                                                value="{{ $item['quantity'] }}"
                                                min="1"
                                                max="{{ $item['stock'] }}"
                                                class="store-quantity-input"
                                                data-cart-quantity
                                                aria-label="Quantidade"
                                            >

                                            <button
                                                type="button"
                                                class="store-quantity-button"
                                                data-action="increase"
                                                aria-label="Aumentar quantidade"
                                                {{ $item['quantity'] >= $item['stock'] ? 'disabled' : '' }}
                                            >
                                                +
                                            </button>

                                        </div>


                                        {{-- Remover --}}
                                        <button
                                            type="button"
                                            class="store-cart-remove"
                                            data-action="remove"
                                        >
                                            Remover
                                        </button>

                                    </div>

                                </div>


                                {{-- Preço --}}
                                <div class="store-cart-price">

                                    <span class="text-sm text-gray-500">
                                        R$ {{ number_format($item['price'], 2, ',', '.') }}
                                        cada
                                    </span>

                                    <strong data-cart-subtotal>
                                        R$ {{ number_format(
                                            $item['price'] * $item['quantity'],
                                            2,
                                            ',',
                                            '.'
                                        ) }}
                                    </strong>

                                </div>

                            </article>

                        @endforeach

                    </div>


                    {{-- Resumo --}}
                    <aside class="store-cart-summary">

                        <h2 class="text-lg font-semibold">
                            Resumo do pedido
                        </h2>


                        <div class="mt-6 flex justify-between gap-4 text-sm text-gray-600">

                            <span>
                                Produtos
                            </span>

                            <span data-cart-summary-count>
                                {{ collect($cart)->sum('quantity') }}
                            </span>

                        </div>


                        <div class="store-cart-total">

                            <span>
                                Total
                            </span>

                            <strong data-cart-total>
                                R$ {{ number_format($total, 2, ',', '.') }}
                            </strong>
                        </div>


                        <button type="button" class="store-button-primary mt-6 w-full" data-whatsapp-number="{{ config('services.whatsapp.number') }}" data-clear-url="{{ route('cart.clear') }}" data-cart-checkout>
                            Finalizar pedido
                        </button>

                        <a href="{{ route('products.index') }}" class="store-cart-continue">
                            Continuar comprando
                        </a>

                    </aside>

                </div>
            @endif

           <div class="store-cart-empty {{ !empty($cart) ? 'hidden' : '' }}" data-cart-empty>
                <h2 class="text-xl font-semibold">
                    Seu carrinho está vazio
                </h2>

                <p class="mt-2 text-gray-500">
                    Explore nossos produtos e escolha o que mais gostar.
                </p>

                <a
                    href="{{ route('products.index') }}"
                    class="store-button-primary mt-6"
                >
                    Ver produtos
                </a>
            </div>

        </div>

    </section>

@endsection