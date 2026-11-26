<?php

namespace App\Support;

class EmailValidation
{
    /**
     * Regra de validação para endereços de e-mail.
     *
     * Em produção exige sintaxe RFC + verificação de DNS/MX do domínio;
     * em desenvolvimento e testes usa sintaxe RFC para não depender de rede.
     */
    public static function rule(): string
    {
        return app()->isProduction() ? 'email:rfc,dns' : 'email:rfc';
    }
}
