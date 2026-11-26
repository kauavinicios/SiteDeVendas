<footer class="store-footer">

    <div class="store-container py-12">

        <div class="grid gap-10 md:grid-cols-2 lg:grid-cols-4">

            {{-- Marca --}}
            <div class="lg:col-span-2">

                <a href="{{ route('home') }}" class="store-footer-brand">

                    <div class="store-logo-symbol">
                        J
                    </div>

                    <div class="store-logo-text">
                        <strong>Butique da Jandira</strong>
                        <span>Casa & Perfumaria</span>
                    </div>

                </a>

                <p class="mt-5 max-w-md text-sm leading-relaxed">
                    Produtos escolhidos com carinho para trazer mais conforto
                    para sua casa e fragrâncias especiais para seus momentos.
                </p>

            </div>


            {{-- Navegação --}}
            <div>

                <h3 class="store-footer-title">
                    Navegação
                </h3>

                <nav class="mt-4 flex flex-col gap-3">

                    <a href="{{ route('home') }}">
                        Início
                    </a>

                    <a href="{{ route('products.index', ['categorias' => ['casa']]) }}">
                        Cama
                    </a>

                    <a href="{{ route('products.index', ['categorias' => ['perfumaria']]) }}">
                        Perfumaria
                    </a>

                    <a href="{{ route('pages.about') }}">
                        Sobre
                    </a>

                    <a href="{{ route('contact.index') }}">
                        Contato
                    </a>

                    <a href="{{ route('legal.terms') }}">
                        Termos de Uso
                    </a>

                    <a href="{{ route('legal.privacy') }}">
                        Política de Privacidade
                    </a>

                </nav>

            </div>


            {{-- Atendimento --}}
            <div>

                <h3 class="store-footer-title">
                    Atendimento
                </h3>

                <div class="mt-4 flex flex-col gap-3 text-sm">

                    <span>
                        Campo Grande - MS
                    </span>

                    @php
                        $footerWhatsappNumber = preg_replace('/\D+/', '', (string) config('services.whatsapp.number'));
                        $footerWhatsappMessage = rawurlencode('Olá! Vim pelo site da Butique da Jandira e gostaria de atendimento.');
                    @endphp

                    <a href="{{ route('contact.index') }}">
                        Formulário de contato
                    </a>

                    @if ($footerWhatsappNumber !== '')
                        <a href="https://wa.me/{{ $footerWhatsappNumber }}?text={{ $footerWhatsappMessage }}" target="_blank" rel="noopener noreferrer">
                            Fale pelo WhatsApp
                        </a>
                    @endif

                    <span>
                        Consulte disponibilidade e entrega
                    </span>

                </div>

            </div>

        </div>


        {{-- Linha inferior --}}
        <div class="store-footer-bottom">

            <p>
                &copy; {{ date('Y') }} Butique da Jandira.
                Todos os direitos reservados.
            </p>

        </div>

    </div>

</footer>