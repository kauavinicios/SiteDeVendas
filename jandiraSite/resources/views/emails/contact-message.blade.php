@extends('emails.layouts.brand')

@section('title', "Novo contato pelo site: {$contactMessage->subject} - Butique da Jandira")
@section('preheader', "Nova mensagem enviada por {$contactMessage->name} através da página de Contato.")
@section('eyebrow', 'Atendimento pelo site')
@section('heading', 'Você recebeu uma nova mensagem')

@section('content')
    <p style="margin: 0 0 16px; color: #554b4e;">
        Uma nova mensagem foi enviada pelo formulário de contato da <strong>Butique da Jandira</strong>.
    </p>

    <div style="margin: 0 0 18px; padding: 16px 18px; background-color: #faf7f5; border: 1px solid #eadeda; border-radius: 12px;">
        <div style="margin-bottom: 8px; color: #322b2d; font-size: 14px;">
            <strong>Remetente:</strong> {{ $contactMessage->name }}
            @if ($contactMessage->user_id !== null)
                <span style="display: inline-block; margin-left: 6px; padding: 2px 8px; background-color: #f7efec; border: 1px solid #eadeda; border-radius: 999px; color: #8b3f59; font-size: 11px; font-weight: 600;">
                    Cliente cadastrado
                </span>
            @endif
        </div>

        <div style="margin-bottom: 6px; color: #554b4e; font-size: 14px;">
            <strong>E-mail:</strong>
            <a href="mailto:{{ $contactMessage->email }}" style="color: #8b3f59; text-decoration: none;">{{ $contactMessage->email }}</a>
        </div>

        @if (! empty($contactMessage->phone))
            <div style="margin-bottom: 6px; color: #554b4e; font-size: 14px;">
                <strong>Telefone / WhatsApp:</strong> {{ $contactMessage->phone }}
            </div>
        @endif

        <div style="margin-bottom: 6px; color: #554b4e; font-size: 14px;">
            <strong>Assunto:</strong> {{ $contactMessage->subject }}
        </div>

        @if ($contactMessage->created_at !== null)
            <div style="color: #74686c; font-size: 12px;">
                Recebida em {{ $contactMessage->created_at->format('d/m/Y H:i') }}
            </div>
        @endif
    </div>

    <div style="margin: 0 0 8px; padding: 18px; background-color: #ffffff; border: 1px solid #eadeda; border-radius: 12px; color: #322b2d; font-size: 14px; line-height: 1.65;">
        <div style="margin-bottom: 8px; color: #8b3f59; font-size: 11px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase;">
            Mensagem
        </div>
        {!! nl2br(e($contactMessage->message)) !!}
    </div>
@endsection

@section('footer')
    <p style="margin: 0 0 6px; color: #74686c;">
        Ao responder diretamente este e-mail, sua resposta será direcionada para <strong>{{ $contactMessage->email }}</strong>.
    </p>
@endsection
