<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmailNotification extends VerifyEmail
{
    /**
     * Constrói o e-mail personalizado da Butique da Jandira para confirmação de e-mail.
     */
    public function toMail($notifiable): MailMessage
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        if (static::$toMailCallback) {
            return call_user_func(static::$toMailCallback, $notifiable, $verificationUrl);
        }

        return (new MailMessage)
            ->subject('Confirme seu e-mail — Butique da Jandira')
            ->view('emails.verify-email', [
                'userName' => $notifiable->name ?? 'cliente',
                'verificationUrl' => $verificationUrl,
                'actionText' => 'Confirmar meu e-mail',
                'actionUrl' => $verificationUrl,
                'expireMinutes' => config('auth.verification.expire', 60),
            ]);
    }
}
