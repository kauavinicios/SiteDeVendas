@extends('emails.layouts.brand')

@section('title', $campaign->subject.' - Butique da Jandira')
@section('preheader', \Illuminate\Support\Str::limit(strip_tags($campaign->body), 120))
@section('eyebrow', 'Novidades da Butique')
@section('heading', $campaign->title)

@section('content')
    <p style="margin: 0 0 16px;">
        Olá, <strong>{{ $recipientName }}</strong>!
    </p>

    <div style="margin: 0; color: #554b4e; font-size: 15px; line-height: 1.7;">
        {!! nl2br(e($campaign->body)) !!}
    </div>
@endsection

@section('footer')
    <p style="margin: 0 0 10px; color: #74686c; font-size: 11px; line-height: 1.5;">
        Você recebeu este e-mail porque autorizou o recebimento de novidades da Butique da Jandira (LGPD).
        Não deseja mais receber nossas campanhas?
        <a href="{{ $unsubscribeUrl }}" style="color: #8b3f59; font-weight: 600; text-decoration: underline;">
            Cancelar inscrição em 1 clique
        </a>
    </p>
@endsection
