@extends('errors.layout')

@section('title', 'Sessão expirada (419)')
@section('code', '419')
@section('heading', 'Sua sessão expirou por segurança.')
@section('message', 'A página ficou aberta por algum tempo e precisamos renovar seu acesso. Recarregue a página ou volte ao início para continuar.')

@section('actions')
    <button type="button" onclick="window.location.reload()" class="error-btn-primary">
        Tentar novamente
    </button>
    <a href="{{ url('/') }}" class="error-btn-secondary">
        Voltar para a página inicial
    </a>
@endsection
