@extends('layouts.app')

@section('title', 'Política de Privacidade - Butique da Jandira')

@section('content')

    <section class="py-10 md:py-14">
        <div class="store-container max-w-3xl">

            <div class="mb-8">
                <span class="store-eyebrow">LGPD &bull; Lei nº 13.709/2018</span>
                <h1 class="mt-2 text-3xl font-semibold text-[var(--color-text)] md:text-4xl">
                    Política de Privacidade
                </h1>
                <p class="mt-2 text-sm text-[var(--color-text-muted)]">
                    Versão {{ $version }} &bull; Última atualização em {{ $updatedAt }}
                </p>
            </div>

            <div class="space-y-6 rounded-2xl border border-[var(--color-border)] bg-white p-6 text-sm leading-relaxed text-[var(--color-text-muted)] shadow-[var(--shadow-sm)] md:p-8">

                <div>
                    <h2 class="text-base font-semibold text-[var(--color-text)]">
                        1. Nosso compromisso com a sua privacidade
                    </h2>
                    <p class="mt-2">
                        A <strong>Butique da Jandira</strong> trata seus dados pessoais com transparência e segurança, em conformidade com a
                        Lei Geral de Proteção de Dados Pessoais (<strong>LGPD — Lei nº 13.709/2018</strong>).
                    </p>
                </div>

                <div>
                    <h2 class="text-base font-semibold text-[var(--color-text)]">
                        2. Quais dados coletamos e guardamos
                    </h2>
                    <ul class="mt-2 list-disc space-y-1.5 pl-5">
                        <li><strong>Dados de cadastro:</strong> nome completo, e-mail, telefone/WhatsApp (opcional) e senha armazenada de forma criptografada (hash).</li>
                        <li><strong>Preferências e aceites:</strong> data e versão de aceite dos Termos de Uso e da Política de Privacidade, confirmação de e-mail e escolha sobre receber ou não novidades promocionais.</li>
                        <li><strong>Dados de compras e interesses:</strong> quando disponíveis na loja, histórico de pedidos realizados, produtos favoritados e alertas solicitados para aviso de retorno ao estoque.</li>
                    </ul>
                </div>

                <div>
                    <h2 class="text-base font-semibold text-[var(--color-text)]">
                        3. Quais dados NÃO armazenamos
                    </h2>
                    <p class="mt-2">
                        <strong>Não coletamos nem armazenamos</strong> números de cartão de crédito, códigos de segurança (CVV), senhas bancárias ou dados sensíveis de pagamento.
                        Todo pagamento combinado pelo WhatsApp ocorre fora do banco de dados da loja. Quando uma plataforma de pagamento homologada for integrada no futuro, esta política será atualizada antes de entrar em operação.
                    </p>
                </div>

                <div>
                    <h2 class="text-base font-semibold text-[var(--color-text)]">
                        4. Para que usamos seus dados (finalidade)
                    </h2>
                    <ul class="mt-2 list-disc space-y-1.5 pl-5">
                        <li>Identificar sua conta, proteger seu acesso e possibilitar a recuperação de senha;</li>
                        <li>Registrar e acompanhar seus pedidos e facilitar o atendimento pelo WhatsApp;</li>
                        <li>Enviar novidades, promoções e avisos de estoque <strong>somente</strong> quando você autorizar expressamente (podendo cancelar a qualquer momento);</li>
                        <li>Cumprir obrigações legais, fiscais e contábeis previstas em lei.</li>
                    </ul>
                </div>

                <div>
                    <h2 class="text-base font-semibold text-[var(--color-text)]">
                        5. Seus direitos e exclusão de conta (LGPD Arts. 16 e 18)
                    </h2>
                    <p class="mt-2">
                        A qualquer momento você pode acessar <a href="{{ route('account.index') }}" class="font-medium text-[var(--color-primary)] underline">Minha conta</a> para:
                    </p>
                    <ul class="mt-2 list-disc space-y-1.5 pl-5">
                        <li>Corrigir ou atualizar seu nome, e-mail e telefone;</li>
                        <li>Ativar ou cancelar o recebimento de e-mails promocionais (LGPD opt-in/opt-out);</li>
                        <li><strong>Excluir sua conta de cliente:</strong> caso não possua pedidos vinculados, seus dados de acesso e perfil são apagados definitivamente. Se já houver pedidos registrados, seus dados pessoais identificáveis (nome, e-mail, telefone e credenciais) são <strong>anonimizados</strong> e o acesso encerrado, mantendo-se apenas os registros numéricos/comerciais das transações exigidos por obrigações legais e fiscais (Art. 16 da LGPD).</li>
                    </ul>
                </div>

                <div>
                    <h2 class="text-base font-semibold text-[var(--color-text)]">
                        6. Compartilhamento de dados
                    </h2>
                    <p class="mt-2">
                        A Butique da Jandira não vende nem compartilha suas informações pessoais com terceiros para fins publicitários externos.
                    </p>
                </div>

            </div>

            <div class="mt-6 flex flex-wrap gap-3">
                <a href="{{ route('home') }}" class="store-button-secondary">
                    ← Voltar para a loja
                </a>
                <a href="{{ route('legal.terms') }}" class="store-button-primary">
                    Ver Termos de Uso
                </a>
            </div>

        </div>
    </section>

@endsection
