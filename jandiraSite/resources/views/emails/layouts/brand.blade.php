<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Butique da Jandira')</title>
</head>

<body style="margin: 0; padding: 0; background-color: #faf7f5; color: #322b2d; font-family: 'Figtree', Arial, Helvetica, sans-serif; -webkit-font-smoothing: antialiased;">
    {{-- Texto oculto de pré-visualização na caixa de entrada --}}
    @hasSection('preheader')
        <div style="display: none; max-height: 0; overflow: hidden; opacity: 0; mso-hide: all; font-size: 1px; line-height: 1px; color: #faf7f5;">
            @yield('preheader')
        </div>
    @endif

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #faf7f5; padding: 32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width: 600px; background-color: #ffffff; border: 1px solid #eadeda; border-radius: 16px; overflow: hidden; box-shadow: 0 8px 24px rgba(66, 44, 51, 0.06);">

                    {{-- Cabeçalho da marca --}}
                    <tr>
                        <td align="center" style="background-color: #f7efec; border-bottom: 1px solid #eadeda; padding: 28px 24px;">
                            <a href="{{ url('/') }}" style="text-decoration: none; display: inline-block;">
                                <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center">
                                    <tr>
                                        <td align="center" valign="middle" style="width: 44px; height: 44px; background-color: #8b3f59; border-radius: 50%; color: #ffffff; font-family: Georgia, 'Times New Roman', serif; font-size: 22px; font-weight: bold; line-height: 44px; text-align: center;">
                                            J
                                        </td>
                                        <td style="padding-left: 12px; text-align: left;">
                                            <div style="color: #6e2f45; font-family: Georgia, 'Times New Roman', serif; font-size: 20px; font-weight: bold; line-height: 1.2;">
                                                Butique da Jandira
                                            </div>
                                            <div style="color: #74686c; font-size: 11px; font-weight: 600; letter-spacing: 1.4px; text-transform: uppercase; margin-top: 3px;">
                                                Casa &amp; Perfumaria
                                            </div>
                                        </td>
                                    </tr>
                                </table>
                            </a>
                        </td>
                    </tr>

                    {{-- Corpo principal --}}
                    <tr>
                        <td style="padding: 36px 32px 28px; background-color: #ffffff;">
                            @hasSection('eyebrow')
                                <div style="color: #8b3f59; font-size: 11px; font-weight: 700; letter-spacing: 1.4px; text-transform: uppercase; margin-bottom: 8px;">
                                    @yield('eyebrow')
                                </div>
                            @endif

                            @hasSection('heading')
                                <h1 style="margin: 0 0 18px; color: #322b2d; font-size: 24px; font-weight: 600; line-height: 1.3;">
                                    @yield('heading')
                                </h1>
                            @endif

                            <div style="color: #322b2d; font-size: 15px; line-height: 1.65;">
                                @yield('content')
                            </div>

                            @if (! empty($actionText) && ! empty($actionUrl))
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 28px 0 22px;">
                                    <tr>
                                        <td align="center">
                                            <a href="{{ $actionUrl }}" target="_blank" rel="noopener noreferrer"
                                                style="display: inline-block; background-color: #8b3f59; color: #ffffff; font-size: 15px; font-weight: 600; text-decoration: none; padding: 13px 28px; border-radius: 999px;">
                                                {{ $actionText }}
                                            </a>
                                        </td>
                                    </tr>
                                </table>

                                <div style="margin-top: 22px; padding-top: 18px; border-top: 1px solid #eadeda; color: #74686c; font-size: 12px; line-height: 1.55;">
                                    Se o botão acima não funcionar, copie e cole este endereço no seu navegador:<br>
                                    <a href="{{ $actionUrl }}" style="color: #8b3f59; word-break: break-all;">{{ $actionUrl }}</a>
                                </div>
                            @endif
                        </td>
                    </tr>

                    {{-- Rodapé institucional --}}
                    <tr>
                        <td align="center" style="background-color: #faf7f5; border-top: 1px solid #eadeda; padding: 22px 24px; color: #74686c; font-size: 12px; line-height: 1.6;">
                            @yield('footer')

                            <p style="margin: 6px 0 0; color: #74686c;">
                                &copy; {{ date('Y') }} Butique da Jandira &mdash; Casa &amp; Perfumaria
                            </p>
                            <p style="margin: 4px 0 0; color: #9a8d91; font-size: 11px;">
                                Atendimento em Campo Grande - MS
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>

</html>
