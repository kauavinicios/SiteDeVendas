<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends ResetPassword
{
    /**
     * Constrói o e-mail personalizado da Butique da Jandira para redefinição de senha.
     */
    public function toMail($notifiable): MailMessage
    {
        if (static::$toMailCallback) {
            return call_user_func(static::$toMailCallback, $notifiable, $this->token);
        }

        $resetUrl = $this->resetUrl($notifiable);

        return (new MailMessage)
            ->subject('Redefinição de senha — Butique da Jandira')
            ->view('emails.reset-password', [
                'userName' => $notifiable->name ?? 'cliente',
                'resetUrl' => $resetUrl,
                'actionText' => 'Redefinir minha senha',
                'actionUrl' => $resetUrl,
                'expireMinutes' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60),
            ]);
    }
}
