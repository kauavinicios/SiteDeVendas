<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;

class SecurityAudit
{
    /**
     * @var list<string>
     */
    private const SENSITIVE_FRAGMENTS = [
        'password',
        'token',
        'secret',
        'api_key',
        'authorization',
        'cookie',
        'cvv',
        'card',
    ];

    /**
     * Registra um evento de segurança estruturado no canal "security",
     * mascarando automaticamente quaisquer campos sensíveis.
     *
     * @param  array<string, mixed>  $context
     */
    public static function record(string $event, array $context = [], string $level = 'info'): void
    {
        $request = app()->bound('request') ? request() : null;
        $user = $request?->user();

        $baseContext = [
            'ip' => $request?->ip(),
            'user_id' => $user?->id,
            'user_role' => $user?->role?->value,
            'path' => $request?->path(),
        ];

        $payload = array_merge($baseContext, self::sanitize($context));

        Log::channel('security')->log($level, "security.{$event}", $payload);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function sanitize(array $data): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            $normalizedKey = strtolower((string) $key);

            if (self::isSensitiveKey($normalizedKey)) {
                $sanitized[$key] = '[REDACTED]';

                continue;
            }

            if (is_array($value)) {
                $sanitized[$key] = self::sanitize($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    private static function isSensitiveKey(string $key): bool
    {
        foreach (self::SENSITIVE_FRAGMENTS as $fragment) {
            if (str_contains($key, $fragment)) {
                return true;
            }
        }

        return false;
    }
}
