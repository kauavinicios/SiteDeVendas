@extends('layouts.app')

@section('title', 'Termos de Uso - Butique da Jandira')

@section('content')

    <section class="py-10 md:py-14">
        <div class="store-container max-w-3xl">

            <div class="mb-8">
                <span class="store-eyebrow">Documentos legais</span>
                <h1 class="mt-2 text-3xl font-semibold text-[var(--color-text)] md:text-4xl">
                    Termos de Uso
                </h1>
                <p class="mt-2 text-sm text-[var(--color-text-muted)]">
                    Versão {{ $version }} &bull; Última atualização em {{ $updatedAt }}
                </p>
            </div>

            <div class="space-y-6 rounded-2xl border border-[var(--color-border)] bg-white p-6 text-sm leading-relaxed text-[var(--color-text-muted)] shadow-[var(--shadow-sm)] md:p-8">

                <div>
                    <h2 class="text-base font-semibold text-[var(--color-text)]">
                        1. Objeto e funcionamento da loja
                    </h2>
                    <p class="mt-2">
                        A <strong>Butique da Jandira — Casa &amp; Perfumaria</strong> disponibiliza seu catálogo online para consulta de produtos,
                        gerenciamento de conta do cliente, montagem de carrinho e solicitação de pedidos com atendimento personalizado via WhatsApp.
                    </p>
                </div>

                <div>
                    <h2 class="text-base font-semibold text-[var(--color-text)]">
                        2. Cadastro e responsabilidade pelas informações
                    </h2>
                    <p class="mt-2">
                        Ao criar uma conta em nosso site, você concorda em fornecer dados verdadeiros e atualizados, especialmente seu endereço de e-mail,
                        que é utilizado para confirmação da conta, recuperação de senha e comunicações sobre seus pedidos. O uso da senha é pessoal e intransferível.
                    </p>
                </div>

                <div>
                    <h2 class="text-base font-semibold text-[var(--color-text)]">
                        3. Pedidos, disponibilidade e pagamentos
                    </h2>
                    <p class="mt-2">
                        Atualmente, os pedidos montados no carrinho são encaminhados para atendimento via <strong>WhatsApp</strong>, onde confirmamos
                        disponibilidade final de estoque, prazo de entrega em Campo Grande - MS (ou envio) e a forma de pagamento combinada diretamente com nossa equipe.
                        O site não solicita nem armazena números de cartão de crédito ou dados bancários.
                    </p>
                </div>

                <div>
                    <h2 class="text-base font-semibold text-[var(--color-text)]">
                        4. Preços, imagens e estoque
                    </h2>
                    <p class="mt-2">
                        Os preços e quantidades exibidos na vitrine podem ser atualizados a qualquer momento conforme reposições e promoções vigentes.
                        Em caso de esgotamento momentâneo de uma variação, o cliente poderá acompanhar a disponibilidade diretamente pela loja.
                    </p>
                </div>

                <div>
                    <h2 class="text-base font-semibold text-[var(--color-text)]">
                        5. Encerramento de conta e privacidade
                    </h2>
                    <p class="mt-2">
                        Você pode atualizar seus dados ou solicitar a exclusão da sua conta de cliente a qualquer momento na página
                        <a href="{{ route('account.index') }}" class="font-medium text-[var(--color-primary)] underline">Minha conta</a>,
                        observadas as regras descritas em nossa
                        <a href="{{ route('legal.privacy') }}" class="font-medium text-[var(--color-primary)] underline">Política de Privacidade</a>.
                    </p>
                </div>

                <div>
                    <h2 class="text-base font-semibold text-[var(--color-text)]">
                        6. Atualizações destes Termos
                    </h2>
                    <p class="mt-2">
                        Estes Termos de Uso poderão ser atualizados sempre que novos recursos forem adicionados ao site (como futura integração de pagamento online).
                        A versão vigente estará sempre disponível nesta página.
                    </p>
                </div>

            </div>

            <div class="mt-6 flex flex-wrap gap-3">
                <a href="{{ route('home') }}" class="store-button-secondary">
                    ← Voltar para a loja
                </a>
                <a href="{{ route('legal.privacy') }}" class="store-button-primary">
                    Ver Política de Privacidade
                </a>
            </div>

        </div>
    </section>

@endsection
