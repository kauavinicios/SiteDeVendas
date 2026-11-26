@extends('emails.layouts.brand')

@section('title', "Voltou ao estoque: {$productName} - Butique da Jandira")
@section('preheader', "{$productName} ({$variantName}) está disponível novamente na Butique da Jandira.")
@section('eyebrow', 'Reposição de estoque')
@section('heading', 'O item que você queria voltou!')

@section('content')
    <p style="margin: 0 0 16px;">
        Olá, <strong>{{ $recipientName }}</strong>!
    </p>

    <p style="margin: 0 0 18px; color: #554b4e;">
        Temos uma ótima notícia: um produto que você estava acompanhando acabou de receber reposição na <strong>Butique da Jandira</strong>.
    </p>

    <div style="margin: 0 0 16px; padding: 16px 18px; background-color: #faf7f5; border: 1px solid #eadeda; border-radius: 12px;">
        <div style="color: #322b2d; font-size: 16px; font-weight: 600;">
            {{ $productName }}
        </div>
        <div style="margin-top: 4px; color: #74686c; font-size: 13px;">
            Opção: <strong>{{ $variantName }}</strong>
        </div>
        <div style="margin-top: 8px; color: #8b3f59; font-size: 18px; font-weight: 700;">
            {{ $priceFormatted }}
        </div>
    </div>

    <p style="margin: 0; color: #74686c; font-size: 13px;">
        As quantidades são limitadas. Clique abaixo para conferir os detalhes e garantir o seu pedido.
    </p>
@endsection
