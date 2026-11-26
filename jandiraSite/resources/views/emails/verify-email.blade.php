@extends('emails.layouts.brand')

@section('title', 'Confirme seu e-mail - Butique da Jandira')
@section('preheader', 'Falta só um clique para confirmar seu e-mail na Butique da Jandira.')
@section('eyebrow', 'Boas-vindas')
@section('heading', 'Confirme seu endereço de e-mail')

@section('content')
    <p style="margin: 0 0 16px;">
        Olá, <strong>{{ $userName }}</strong>!
    </p>

    <p style="margin: 0 0 16px; color: #554b4e;">
        Obrigado por se cadastrar na <strong>Butique da Jandira</strong>.
        Para garantir que este e-mail é realmente seu e ativar o recebimento de novidades (caso tenha optado), clique no botão abaixo:
    </p>

    <p style="margin: 0; padding: 14px 16px; background-color: #faf7f5; border: 1px solid #eadeda; border-radius: 10px; color: #74686c; font-size: 13px;">
        Este link de confirmação é válido por <strong>{{ $expireMinutes }} minutos</strong>.
        Se você não criou uma conta em nossa loja, pode desconsiderar esta mensagem.
    </p>
@endsection
