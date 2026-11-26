@extends('errors.layout')

@section('title', 'Voltamos já (503)')
@section('code', '503')
@section('heading', 'Estamos preparando novidades para você.')
@section('message', 'A Butique da Jandira está passando por uma breve manutenção para melhorar sua experiência. Voltamos em instantes!')

@section('actions')
    <button type="button" onclick="window.location.reload()" class="error-btn-primary">
        Atualizar página
    </button>
@endsection
