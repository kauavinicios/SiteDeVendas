<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SecurityCheckCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'app:security-check {--strict : Falha também em avisos (WARN)}';

    /**
     * @var string
     */
    protected $description = 'Verifica a postura de segurança e criptografia das configurações da aplicação';

    public function handle(): int
    {
        $isProduction = app()->isProduction();
        $appKey = (string) config('app.key', '');
        $appDebug = (bool) config('app.debug', false);
        $appUrl = (string) config('app.url', '');
        $sessionEncrypt = (bool) config('session.encrypt', false);
        $sessionHttpOnly = (bool) config('session.http_only', false);
        $sessionSecure = (bool) config('session.secure', false);
        $infinitePayDriver = strtolower(trim((string) config('services.infinitepay.driver', 'mock')));
        $webhookSecret = trim((string) config('services.infinitepay.webhook_secret', ''));
        $trustedProxies = config('trustedproxy.proxies');
        $hasGd = extension_loaded('gd');

        $rows = [];

        $rows[] = $this->evaluateAppKey($appKey);
        $rows[] = $this->evaluateDebug($appDebug, $isProduction);
        $rows[] = $this->evaluateAppUrl($appUrl, $isProduction);
        $rows[] = $this->evaluateBoolean('SESSION_ENCRYPT', $sessionEncrypt, 'Sessões criptografadas no armazenamento.', 'SESSION_ENCRYPT está desativado.');
        $rows[] = $this->evaluateBoolean('SESSION_HTTP_ONLY', $sessionHttpOnly, 'Cookie de sessão protegido contra JavaScript.', 'SESSION_HTTP_ONLY está desativado.');
        $rows[] = $this->evaluateSessionSecure($sessionSecure, $isProduction);
        $rows[] = $this->evaluateInfinitePayDriver($infinitePayDriver, $isProduction);
        $rows[] = $this->evaluateWebhookSecret($webhookSecret, $infinitePayDriver, $isProduction);
        $rows[] = $this->evaluateTrustedProxies($trustedProxies, $isProduction);
        $rows[] = $this->evaluateGdExtension($hasGd);

        $this->table(['Verificação', 'Status', 'Detalhes'], $rows);

        $hasFailure = collect($rows)->contains(fn (array $row): bool => $row['status'] === 'FAIL');
        $hasWarning = collect($rows)->contains(fn (array $row): bool => $row['status'] === 'WARN');

        if ($hasFailure || ($this->option('strict') && $hasWarning)) {
            $this->error('Verificação de segurança reprovada. Corrija os itens apontados antes de publicar.');

            return self::FAILURE;
        }

        $this->info('Postura de segurança verificada com sucesso.');

        return self::SUCCESS;
    }

    /**
     * @return array{check: string, status: string, details: string}
     */
    private function evaluateAppKey(string $appKey): array
    {
        $valid = strlen($appKey) >= 32;

        return [
            'check' => 'APP_KEY',
            'status' => $valid ? 'PASS' : 'FAIL',
            'details' => $valid
                ? 'Chave de criptografia AES-256 configurada.'
                : 'APP_KEY ausente ou muito curta (execute php artisan key:generate).',
        ];
    }

    /**
     * @return array{check: string, status: string, details: string}
     */
    private function evaluateDebug(bool $debug, bool $isProduction): array
    {
        if (! $debug) {
            return [
                'check' => 'APP_DEBUG',
                'status' => 'PASS',
                'details' => 'Modo debug desativado.',
            ];
        }

        return [
            'check' => 'APP_DEBUG',
            'status' => $isProduction ? 'FAIL' : 'WARN',
            'details' => $isProduction
                ? 'APP_DEBUG=true em produção expõe stack traces e variáveis!'
                : 'APP_DEBUG=true (aceito apenas em desenvolvimento/testes).',
        ];
    }

    /**
     * @return array{check: string, status: string, details: string}
     */
    private function evaluateAppUrl(string $appUrl, bool $isProduction): array
    {
        if (str_starts_with($appUrl, 'https://')) {
            return [
                'check' => 'APP_URL',
                'status' => 'PASS',
                'details' => 'URL base utiliza HTTPS.',
            ];
        }

        return [
            'check' => 'APP_URL',
            'status' => $isProduction ? 'FAIL' : 'WARN',
            'details' => $isProduction
                ? 'APP_URL em produção deve iniciar com https://.'
                : "APP_URL sem HTTPS ({$appUrl}); aceito apenas em ambiente local/testes.",
        ];
    }

    /**
     * @return array{check: string, status: string, details: string}
     */
    private function evaluateBoolean(string $check, bool $enabled, string $okMessage, string $failMessage): array
    {
        return [
            'check' => $check,
            'status' => $enabled ? 'PASS' : 'FAIL',
            'details' => $enabled ? $okMessage : $failMessage,
        ];
    }

    /**
     * @return array{check: string, status: string, details: string}
     */
    private function evaluateSessionSecure(bool $sessionSecure, bool $isProduction): array
    {
        if ($sessionSecure) {
            return [
                'check' => 'SESSION_SECURE_COOKIE',
                'status' => 'PASS',
                'details' => 'Cookie de sessão exige HTTPS.',
            ];
        }

        return [
            'check' => 'SESSION_SECURE_COOKIE',
            'status' => $isProduction ? 'FAIL' : 'WARN',
            'details' => $isProduction
                ? 'SESSION_SECURE_COOKIE deve ser true em produção.'
                : 'Cookie sem flag Secure para permitir HTTP local/testes.',
        ];
    }

    /**
     * @return array{check: string, status: string, details: string}
     */
    private function evaluateInfinitePayDriver(string $driver, bool $isProduction): array
    {
        if ($driver === 'mock' && $isProduction) {
            return [
                'check' => 'INFINITEPAY_DRIVER',
                'status' => 'FAIL',
                'details' => 'Driver mock não pode ser utilizado em produção.',
            ];
        }

        return [
            'check' => 'INFINITEPAY_DRIVER',
            'status' => 'PASS',
            'details' => "Driver ativo: {$driver}.",
        ];
    }

    /**
     * @return array{check: string, status: string, details: string}
     */
    private function evaluateWebhookSecret(string $secret, string $driver, bool $isProduction): array
    {
        $length = strlen($secret);

        if ($driver === 'live' && $length < 24) {
            return [
                'check' => 'INFINITEPAY_WEBHOOK_SECRET',
                'status' => $isProduction ? 'FAIL' : 'WARN',
                'details' => $length === 0
                    ? 'INFINITEPAY_WEBHOOK_SECRET não configurado para o driver live.'
                    : 'Segredo de webhook curto (recomenda-se >= 24 caracteres).',
            ];
        }

        return [
            'check' => 'INFINITEPAY_WEBHOOK_SECRET',
            'status' => 'PASS',
            'details' => $driver === 'live'
                ? 'Segredo do webhook configurado com comprimento adequado.'
                : 'Simulador mock ativo em ambiente não produtivo.',
        ];
    }

    /**
     * @param  array<int, string>|string|null  $trustedProxies
     * @return array{check: string, status: string, details: string}
     */
    private function evaluateTrustedProxies(array|string|null $trustedProxies, bool $isProduction): array
    {
        $configured = (is_string($trustedProxies) && trim($trustedProxies) !== '')
            || (is_array($trustedProxies) && $trustedProxies !== []);

        if ($configured) {
            return [
                'check' => 'TRUSTED_PROXIES',
                'status' => 'PASS',
                'details' => 'Proxies confiáveis configurados para proxy reverso/Caddy.',
            ];
        }

        return [
            'check' => 'TRUSTED_PROXIES',
            'status' => $isProduction ? 'WARN' : 'PASS',
            'details' => $isProduction
                ? 'TRUSTED_PROXIES não definido; em produção atrás de Caddy/proxy defina TRUSTED_PROXIES (ex.: * ou sub-rede Docker).'
                : 'Opcional fora de produção (não definido).',
        ];
    }

    /**
     * @return array{check: string, status: string, details: string}
     */
    private function evaluateGdExtension(bool $hasGd): array
    {
        return [
            'check' => 'PHP_EXT_GD',
            'status' => $hasGd ? 'PASS' : 'WARN',
            'details' => $hasGd
                ? 'Extensão PHP GD ativa para sanitização de metadados EXIF em uploads.'
                : 'Extensão PHP GD ausente: uploads funcionam, mas sem remoção de metadados EXIF (incluída na imagem Docker).',
        ];
    }
}
