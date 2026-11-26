<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Erro') - Butique da Jandira</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet">

    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background: linear-gradient(155deg, #fffdfb 0%, #f9f1ee 100%);
            color: #322b2d;
            font-family:
                'Figtree',
                system-ui,
                -apple-system,
                BlinkMacSystemFont,
                'Segoe UI',
                sans-serif;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }

        .error-header {
            padding: 32px 20px 12px;
            display: flex;
            justify-content: center;
        }

        .error-brand {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }

        .error-logo-symbol {
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            background: #8b3f59;
            color: #ffffff;
            border-radius: 50%;
            font-family: Georgia, 'Times New Roman', serif;
            font-size: 24px;
            font-weight: 600;
            box-shadow: 0 6px 16px rgba(139, 63, 89, 0.18);
        }

        .error-logo-text {
            display: flex;
            flex-direction: column;
            line-height: 1.15;
        }

        .error-logo-text strong {
            color: #6e2f45;
            font-family: Georgia, 'Times New Roman', serif;
            font-size: 20px;
            font-weight: 600;
        }

        .error-logo-text span {
            margin-top: 3px;
            color: #74686c;
            font-size: 11px;
            font-weight: 500;
            letter-spacing: 1.2px;
            text-transform: uppercase;
        }

        .error-main {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 20px 48px;
        }

        .error-card {
            width: 100%;
            max-width: 540px;
            padding: 44px 36px;
            background: #ffffff;
            border: 1px solid #eadeda;
            border-radius: 24px;
            box-shadow: 0 12px 34px rgba(66, 44, 51, 0.08);
            text-align: center;
        }

        .error-badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 14px;
            background: #f7efec;
            border: 1px solid #eadeda;
            border-radius: 999px;
            color: #8b3f59;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .error-code {
            margin: 18px 0 6px;
            color: #8b3f59;
            font-family: Georgia, 'Times New Roman', serif;
            font-size: clamp(64px, 12vw, 88px);
            font-weight: 600;
            line-height: 1;
            letter-spacing: -0.02em;
        }

        .error-heading {
            margin: 10px 0 12px;
            color: #322b2d;
            font-size: 24px;
            font-weight: 600;
            line-height: 1.3;
        }

        .error-message {
            margin: 0 auto 28px;
            max-width: 420px;
            color: #74686c;
            font-size: 15px;
            line-height: 1.65;
        }

        .error-actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }

        .error-btn-primary,
        .error-btn-secondary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 44px;
            padding: 0 22px;
            border-radius: 999px;
            font: inherit;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition:
                transform 0.2s ease,
                background-color 0.2s ease,
                border-color 0.2s ease,
                color 0.2s ease;
        }

        .error-btn-primary {
            background: #8b3f59;
            border: 1px solid #8b3f59;
            color: #ffffff;
            box-shadow: 0 6px 20px rgba(139, 63, 89, 0.16);
        }

        .error-btn-primary:hover {
            background: #6e2f45;
            border-color: #6e2f45;
            transform: translateY(-1px);
        }

        .error-btn-secondary {
            background: transparent;
            border: 1px solid #c98ca0;
            color: #8b3f59;
        }

        .error-btn-secondary:hover {
            background: #f7efec;
        }

        .error-footer {
            padding: 0 20px 28px;
            text-align: center;
            color: #74686c;
            font-size: 12px;
        }

        @media (max-width: 600px) {
            .error-card {
                padding: 32px 22px;
                border-radius: 18px;
            }

            .error-heading {
                font-size: 20px;
            }

            .error-actions {
                flex-direction: column;
                width: 100%;
            }

            .error-btn-primary,
            .error-btn-secondary {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <header class="error-header">
        <a href="{{ url('/') }}" class="error-brand" aria-label="Ir para a página inicial da Butique da Jandira">
            <div class="error-logo-symbol">J</div>
            <div class="error-logo-text">
                <strong>Butique da Jandira</strong>
                <span>Casa &amp; Perfumaria</span>
            </div>
        </a>
    </header>

    <main class="error-main">
        <section class="error-card">
            <span class="error-badge">Erro @yield('code')</span>

            <p class="error-code" aria-hidden="true">@yield('code')</p>

            <h1 class="error-heading">@yield('heading')</h1>

            <p class="error-message">@yield('message')</p>

            <div class="error-actions">
                @section('actions')
                    <a href="{{ url('/') }}" class="error-btn-primary">
                        Voltar para a página inicial
                    </a>
                    <a href="{{ url('/produtos') }}" class="error-btn-secondary">
                        Ver catálogo de produtos
                    </a>
                @show
            </div>
        </section>
    </main>

    <footer class="error-footer">
        &copy; {{ date('Y') }} Butique da Jandira &bull; Atendimento em Campo Grande - MS
    </footer>
</body>

</html>
