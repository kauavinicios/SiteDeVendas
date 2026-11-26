<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Mail\ContactMessageReceivedMail;
use App\Models\ContactMessage;
use App\Support\EmailValidation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function index(Request $request): View
    {
        $whatsappDigits = preg_replace('/\D+/', '', (string) config('services.whatsapp.number'));
        $whatsappMessage = rawurlencode('Olá! Vim pela página de Contato da Butique da Jandira e gostaria de atendimento.');

        $whatsappUrl = $whatsappDigits !== ''
            ? "https://wa.me/{$whatsappDigits}?text={$whatsappMessage}"
            : null;

        $user = $request->user();

        return view('pages.contact', [
            'whatsappUrl' => $whatsappUrl,
            'supportEmail' => config('mail.from.address'),
            'prefill' => [
                'name' => $user?->name ?? '',
                'email' => $user?->email ?? '',
                'phone' => $user?->phone ?? '',
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'name' => trim((string) $request->input('name')),
            'email' => mb_strtolower(trim((string) $request->input('email'))),
            'phone' => trim((string) $request->input('phone')),
            'subject' => trim((string) $request->input('subject')),
            'message' => trim((string) $request->input('message')),
        ]);

        $validated = $request->validate(
            [
                'name' => ['required', 'string', 'min:2', 'max:120'],
                'email' => ['required', 'string', EmailValidation::rule(), 'max:255'],
                'phone' => ['nullable', 'string', 'max:30'],
                'subject' => ['required', 'string', 'min:3', 'max:160'],
                'message' => ['required', 'string', 'min:10', 'max:3000'],
            ],
            [
                'name.required' => 'Informe seu nome completo.',
                'name.min' => 'O nome deve ter pelo menos 2 caracteres.',
                'name.max' => 'O nome pode ter no máximo 120 caracteres.',
                'email.required' => 'Informe seu endereço de e-mail.',
                'email.email' => 'Informe um endereço de e-mail válido.',
                'email.max' => 'O e-mail pode ter no máximo 255 caracteres.',
                'phone.max' => 'O telefone pode ter no máximo 30 caracteres.',
                'subject.required' => 'Informe o assunto da mensagem.',
                'subject.min' => 'O assunto deve ter pelo menos 3 caracteres.',
                'subject.max' => 'O assunto pode ter no máximo 160 caracteres.',
                'message.required' => 'Escreva sua mensagem para nossa equipe.',
                'message.min' => 'A mensagem deve ter pelo menos 10 caracteres.',
                'message.max' => 'A mensagem pode ter no máximo 3000 caracteres.',
            ],
        );

        $phone = $validated['phone'] !== '' ? $validated['phone'] : null;

        $contactMessage = ContactMessage::create([
            'user_id' => $request->user()?->id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $phone,
            'subject' => $validated['subject'],
            'message' => $validated['message'],
        ]);

        $recipientEmail = (string) config('mail.from.address');

        if ($recipientEmail !== '') {
            Mail::to($recipientEmail)->queue(new ContactMessageReceivedMail($contactMessage));
        }

        return redirect()
            ->route('contact.index')
            ->with('contact_success', 'Mensagem enviada com sucesso! Nossa equipe responderá em breve.');
    }
}
