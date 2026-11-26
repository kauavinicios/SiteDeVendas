@extends('emails.layouts.brand')

@section('title', 'Redefinição de senha - Butique da Jandira')
@section('preheader', 'Recebemos uma solicitação para redefinir sua senha na Butique da Jandira.')
@section('eyebrow', 'Segurança da conta')
@section('heading', 'Redefinição de senha')

@section('content')
    <p style="margin: 0 0 16px;">
        Olá, <strong>{{ $userName }}</strong>!
    </p>

    <p style="margin: 0 0 16px; color: #554b4e;">
        Recebemos uma solicitação para redefinir a senha da sua conta na <strong>Butique da Jandira</strong>.
        Para cadastrar uma nova senha, clique no botão abaixo:
    </p>

    <p style="margin: 0; padding: 14px 16px; background-color: #faf7f5; border: 1px solid #eadeda; border-radius: 10px; color: #74686c; font-size: 13px;">
        Este link é válido por <strong>{{ $expireMinutes }} minutos</strong>.
        Se você não solicitou a redefinição de senha, nenhuma alteração será feita na sua conta e você pode ignorar este e-mail com segurança.
    </p>
@endsection
