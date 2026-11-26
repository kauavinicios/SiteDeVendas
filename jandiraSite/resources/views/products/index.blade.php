@extends('layouts.app')

@section('title', 'Produtos - Butique da Jandira')

@push('styles')
    @vite('resources/css/shop/pages/products.css')
@endpush

@section('content')

    <section class="store-products-page">

        <div class="store-container py-10 md:py-14">

            {{-- Cabeçalho --}}
            <div class="mb-10">

                <span class="store-eyebrow">
                    Nossa loja
                </span>

                <h1 class="mt-2 text-3xl font-semibold md:text-4xl">
                    @if (request('busca'))
                        Resultados para "{{ request('busca') }}"
                    @else
                        Todos os produtos
                    @endif
                </h1>

                <p class="mt-3 max-w-2xl text-gray-600">
                    Confira os produtos disponíveis em nossa boutique.
                </p>

            </div>


            {{-- Área principal --}}
            <div class="grid gap-8 lg:grid-cols-[240px_1fr]">

                {{-- Filtros --}}
                <aside class="store-filter-panel">

                    <form action="{{ route('products.index') }}" method="GET">

                        <div class="flex items-center justify-between gap-4">

                            <h2 class="text-lg font-semibold">
                                Filtrar produtos
                            </h2>

                            @if (request()->filled('categorias'))
                                                    <a href="{{ route('products.index', array_filter([
                                    'busca' => request('busca'),
                                    'ordenacao' => request('ordenacao'),
                                ])) }}" class="store-clear-filters">
                                                        Limpar
                                                    </a>
                            @endif

                        </div>


                        {{-- Mantém a busca --}}
                        @if (request('busca'))
                            <input type="hidden" name="busca" value="{{ request('busca') }}">
                        @endif


                        {{-- Mantém a ordenação --}}
                        @if (request('ordenacao'))
                            <input type="hidden" name="ordenacao" value="{{ request('ordenacao') }}">
                        @endif


                        <div class="mt-6">

                            <h3 class="text-sm font-semibold">
                                Categoria
                            </h3>

                            <div class="mt-4 flex flex-col gap-5">

                                @foreach ($categorias as $categoria)

                                                            <div>

                                                                <label class="store-checkbox-label">

                                                                    <input type="checkbox" name="categorias[]" value="{{ $categoria->slug }}"
                                                                        class="store-checkbox" {{ in_array(
                                        $categoria->slug,
                                        (array) request('categorias', [])
                                    ) ? 'checked' : '' }}>

                                                                    <span>
                                                                        {{ $categoria->name }}
                                                                    </span>

                                                                </label>


                                                                @if ($categoria->children->isNotEmpty())

                                                                    <div class="mt-3 flex flex-col gap-3 border-l border-gray-200 pl-5">

                                                                        @foreach ($categoria->children as $subcategoria)

                                                                                                    <label class="store-checkbox-label">

                                                                                                        <input type="checkbox" name="categorias[]" value="{{ $subcategoria->slug }}"
                                                                                                            class="store-checkbox" {{ in_array(
                                                                                $subcategoria->slug,
                                                                                (array) request('categorias', [])
                                                                            ) ? 'checked' : '' }}>

                                                                                                        <span>
                                                                                                            {{ $subcategoria->name }}
                                                                                                        </span>

                                                                                                    </label>

                                                                        @endforeach

                                                                    </div>

                                                                @endif

                                                            </div>

                                @endforeach

                            </div>

                        </div>


                        <button type="submit" class="store-filter-button mt-6 w-full">
                            Aplicar filtros
                        </button>

                    </form>

                </aside>


                {{-- Produtos --}}
                <div>

                    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

                        <p class="text-sm text-gray-500">
                            {{ $produtos->total() }}
                            {{ $produtos->total() === 1 ? 'produto encontrado' : 'produtos encontrados' }}
                        </p>

                        <form action="{{ route('products.index') }}" method="GET" class="flex items-center gap-3">

                            @if (request('busca'))
                                <input type="hidden" name="busca" value="{{ request('busca') }}">
                            @endif

                            @foreach ((array) request('categorias', []) as $categoriaSelecionada)
                                <input type="hidden" name="categorias[]" value="{{ $categoriaSelecionada }}">
                            @endforeach

                            <label for="ordenacao" class="text-sm text-gray-500">
                                Ordenar por
                            </label>

                            <select id="ordenacao" name="ordenacao" class="store-sort-select" onchange="this.form.submit()">
                                <option value="" {{ !request('ordenacao') ? 'selected' : '' }}>
                                    Mais recentes
                                </option>

                                <option value="menor-preco" {{ request('ordenacao') === 'menor-preco' ? 'selected' : '' }}>
                                    Menor preço
                                </option>

                                <option value="maior-preco" {{ request('ordenacao') === 'maior-preco' ? 'selected' : '' }}>
                                    Maior preço
                                </option>

                                <option value="nome" {{ request('ordenacao') === 'nome' ? 'selected' : '' }}>
                                    Nome A-Z
                                </option>
                            </select>

                        </form>

                    </div>


                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-3">

                        @forelse ($produtos as $produto)

                            @php
                                $variacoesAtivas = $produto->variants
                                    ->where('active', true);

                                $menorPreco = $variacoesAtivas->min('price');
                            @endphp

                            <article class="store-product-card group">

                                <div class="store-product-image">

                                    @if ($produto->images->isNotEmpty())
                                        <img src="{{ $produto->images->first()->url }}" alt="{{ $produto->name }}"
                                            class="h-full w-full object-cover" loading="lazy">
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

                                    <h2 class="mt-2 line-clamp-2 text-lg font-semibold">
                                        {{ $produto->name }}
                                    </h2>

                                    <div class="mt-5 flex items-center justify-between gap-4">

                                        @if ($menorPreco !== null)

                                            <div>

                                                @if ($variacoesAtivas->count() > 1)
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

                                        <a href="{{ route('products.show', $produto) }}" class="store-product-button">
                                            Ver produto
                                        </a>

                                    </div>

                                </div>

                            </article>

                        @empty

                            <div class="col-span-full rounded-xl border border-gray-200 bg-white p-10 text-center">

                                <p class="text-gray-500">
                                    Nenhum produto encontrado.
                                </p>

                            </div>

                        @endforelse

                    </div>


                    {{-- Paginação --}}
                    @if ($produtos->hasPages())

                        <div class="mt-10">
                            {{ $produtos->links() }}
                        </div>

                    @endif

                </div>

            </div>

        </div>

    </section>

@endsection