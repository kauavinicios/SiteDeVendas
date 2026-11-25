<header class="store-header">

    {{-- Barra superior --}}
    <div class="store-topbar">
        <div class="store-container store-topbar-content">
            <span>Atendimento em Campo Grande - MS</span>

            <span class="store-topbar-highlight">
                Pedidos e informações pelo WhatsApp
            </span>
        </div>
    </div>

    {{-- Cabeçalho principal --}}
    <div class="store-main-header">
        <div class="store-container store-main-header-content">

            {{-- Logo --}}
            <a href="{{ route('home') }}" class="store-logo">
                <div class="store-logo-symbol">
                    J
                </div>

                <div class="store-logo-text">
                    <strong>Butique da Jandira</strong>
                    <span>Casa & Perfumaria</span>
                </div>
            </a>

            {{-- Busca --}}
            <form action="{{ route('products.index') }}" method="GET" class="store-search">

                <input type="search" name="busca" value="{{ request('busca') }}" placeholder="Buscar produtos..." autocomplete="off">

                <button type="submit" aria-label="Pesquisar">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.3-4.3"></path>
                    </svg>
                </button>
            </form>

            {{-- Ações --}}
            <div class="store-actions">

                @auth
                    <a href="{{ auth()->user()->isStaff() ? route('dashboard') : route('account.index') }}" class="store-action">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2">
                            <path d="M20 21a8 8 0 0 0-16 0"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>

                        <span>{{ auth()->user()->isStaff() ? 'Painel Admin' : 'Minha conta' }}</span>
                    </a>
                @else
                    <a href="{{ route('login') }}" class="store-action">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2">
                            <path d="M20 21a8 8 0 0 0-16 0"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>

                        <span>Entrar</span>
                    </a>
                @endauth

                <a href="{{ route('cart.index') }}" class="store-action">
                    <svg xmlns="http://www.w3.org/2000/svg" width="23" height="23" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2">
                        <circle cx="9" cy="20" r="1"></circle>
                        <circle cx="19" cy="20" r="1"></circle>
                        <path d="M3 4h2l2.5 11h10l2-7H7"></path>
                    </svg>

                    <span>Carrinho</span>
                    @php
                        $cartCount = collect(session('cart', []))->sum('quantity');
                    @endphp

                    <span class="store-cart-count" {{ $cartCount === 0 ? 'hidden' : '' }} data-cart-count>
                        {{ $cartCount }}
                    </span>

                </a>

            </div>
        </div>
    </div>

    {{-- Navegação desktop --}}
    <nav class="store-navigation">
        <div class="store-container">
            <div class="store-nav-links">

                <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}">
                    Início
                </a>

                <a href="{{ route('products.index', ['categorias' => 'casa']) }}" class="{{ request('categoria') === 'casa' ? 'active' : '' }}">
                    Cama
                </a>
                
                <a href="{{ route('products.index', ['categorias' => 'perfumaria']) }}" class="{{ request('categoria') === 'perfumaria' ? 'active' : '' }}">
                    Perfumaria
                </a>

                <a href="#">
                    Sobre
                </a>

                <a href="#">
                    Contato
                </a>

            </div>
        </div>
    </nav>

</header>