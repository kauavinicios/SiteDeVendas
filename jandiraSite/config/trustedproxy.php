<?php

$raw = trim((string) env('TRUSTED_PROXIES', ''));

if ($raw === '') {
    $proxies = null;
} elseif ($raw === '*' || $raw === '**') {
    $proxies = $raw;
} else {
    $parsed = array_values(array_filter(array_map('trim', explode(',', $raw))));
    $proxies = $parsed === [] ? null : $parsed;
}

return [

    /*
    |--------------------------------------------------------------------------
    | Proxies Confiáveis (Caddy / Proxy Reverso)
    |--------------------------------------------------------------------------
    |
    | Illuminate\Http\Middleware\TrustProxies lê config('trustedproxy.proxies')
    | nativamente em cada requisição, mantendo compatibilidade total com
    | "php artisan config:cache" em produção.
    |
    */

    'proxies' => $proxies,

];
