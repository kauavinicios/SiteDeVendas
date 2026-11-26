<?php

namespace App\Services;

use App\Models\Order;
use App\Services\Payments\CheckoutLinkResult;
use App\Services\Payments\InfinitePayException;
use App\Services\Payments\PaymentCheckResult;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class InfinitePayService
{
    public function driver(): string
    {
        $driver = strtolower(trim((string) config('services.infinitepay.driver', 'mock')));

        return $driver === 'live' ? 'live' : 'mock';
    }

    public function isMock(): bool
    {
        return $this->driver() === 'mock';
    }

    public function isLive(): bool
    {
        return $this->driver() === 'live';
    }

    public function baseUrl(): string
    {
        return rtrim((string) config('services.infinitepay.base_url', 'https://api.checkout.infinitepay.io'), '/');
    }

    public function handle(): string
    {
        return ltrim(trim((string) config('services.infinitepay.handle', '')), '$');
    }

    public function apiKey(): string
    {
        return trim((string) config('services.infinitepay.api_key', ''));
    }

    public function hasApiKey(): bool
    {
        return $this->apiKey() !== '';
    }

    public function authMode(): string
    {
        if ($this->isMock()) {
            return 'mock';
        }

        return $this->hasApiKey() ? 'api_key' : 'handle';
    }

    public function isConfigured(): bool
    {
        return $this->isMock() || $this->hasApiKey() || $this->handle() !== '';
    }

    public function webhookSecret(): string
    {
        return trim((string) config('services.infinitepay.webhook_secret', ''));
    }

    public function isValidWebhookSecret(?string $secret): bool
    {
        $expected = $this->webhookSecret();
        $provided = trim((string) $secret);

        if ($expected === '' || $provided === '') {
            return false;
        }

        return hash_equals($expected, $provided);
    }

    public function toCents(float|int|string $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }

    public function formatPhoneNumber(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if ($digits === null || $digits === '') {
            return null;
        }

        if (strlen($digits) === 10 || strlen($digits) === 11) {
            return '+55'.$digits;
        }

        if ((strlen($digits) === 12 || strlen($digits) === 13) && str_starts_with($digits, '55')) {
            return '+'.$digits;
        }

        return null;
    }

    public function mockCacheKey(string $slug): string
    {
        return "infinitepay:mock:{$slug}";
    }

    /**
     * Cria um link de checkout na InfinitePay (ou simulado no modo mock).
     *
     * @param  array<string, mixed>  $extraOptions
     */
    public function createCheckoutLink(
        Order $order,
        ?string $redirectUrl = null,
        ?string $webhookUrl = null,
        array $extraOptions = [],
    ): CheckoutLinkResult {
        $order->loadMissing('items');

        if ($order->items->isEmpty()) {
            throw new InfinitePayException('O pedido não possui itens para gerar o checkout na InfinitePay.');
        }

        $items = [];
        $totalCents = 0;

        foreach ($order->items as $item) {
            $quantity = (int) $item->quantity;
            $priceCents = $this->toCents($item->unit_price);

            if ($quantity <= 0 || $priceCents <= 0) {
                throw new InfinitePayException('Todos os itens do pedido devem possuir quantidade e preço válidos para o checkout.');
            }

            $description = $item->variant_name
                ? "{$item->product_name} ({$item->variant_name})"
                : $item->product_name;

            $items[] = [
                'description' => Str::limit($description, 255, ''),
                'quantity' => $quantity,
                'price' => $priceCents,
            ];

            $totalCents += $priceCents * $quantity;
        }

        if ($this->isMock()) {
            $slug = 'mock-'.Str::lower(Str::random(12));
            $url = url("/dev/infinitepay/checkout/{$slug}");

            $snapshot = [
                'success' => true,
                'slug' => $slug,
                'url' => $url,
                'order_nsu' => $order->code,
                'amount' => $totalCents,
                'paid' => false,
                'paid_amount' => null,
                'transaction_nsu' => null,
                'capture_method' => null,
                'installments' => null,
                'receipt_url' => null,
                'redirect_url' => $redirectUrl,
                'webhook_url' => $webhookUrl,
                'items' => $items,
            ];

            Cache::put($this->mockCacheKey($slug), $snapshot, now()->addHours(6));

            return new CheckoutLinkResult(
                url: $url,
                slug: $slug,
                orderNsu: $order->code,
                amountCents: $totalCents,
                driver: 'mock',
                authMode: 'mock',
                rawPayload: $snapshot,
            );
        }

        $this->ensureLiveCredentialsConfigured();

        $payload = [
            'order_nsu' => $order->code,
            'items' => $items,
        ];

        if ($this->handle() !== '') {
            $payload['handle'] = $this->handle();
        }

        $customer = array_filter([
            'name' => trim((string) $order->customer_name),
            'email' => trim((string) $order->customer_email),
            'phoneNumber' => $this->formatPhoneNumber($order->customer_phone),
        ], fn (?string $value): bool => $value !== null && $value !== '');

        if ($customer !== []) {
            $payload['customer'] = $customer;
        }

        if (filled($redirectUrl)) {
            $payload['redirect_url'] = trim((string) $redirectUrl);
        }

        if (filled($webhookUrl)) {
            $payload['webhook_url'] = trim((string) $webhookUrl);
        }

        // Opções avançadas de link são enviadas somente quando X-Api-Key está configurada
        if ($this->hasApiKey()) {
            $payload['options'] = array_merge([
                'single_payment' => (bool) config('services.infinitepay.single_payment', true),
                'require_delivery_address' => (bool) config('services.infinitepay.require_delivery_address', false),
            ], $extraOptions);
        }

        $response = $this->sendPost('/links', $payload);
        $body = $this->decodeJson($response);

        $url = trim((string) ($body['url'] ?? ''));

        if ($url === '') {
            throw new InfinitePayException(
                'A resposta da InfinitePay não retornou a URL do checkout.',
                httpStatus: $response->status(),
                responseBody: $body,
            );
        }

        $slug = trim((string) ($body['slug'] ?? ''));

        if ($slug === '') {
            $path = (string) parse_url($url, PHP_URL_PATH);
            $slug = trim((string) basename($path));
        }

        $orderNsu = trim((string) ($body['order_nsu'] ?? $order->code));
        $amountCents = isset($body['amount']) ? (int) $body['amount'] : $totalCents;

        return new CheckoutLinkResult(
            url: $url,
            slug: $slug !== '' ? $slug : $order->code,
            orderNsu: $orderNsu !== '' ? $orderNsu : $order->code,
            amountCents: $amountCents,
            driver: 'live',
            authMode: $this->authMode(),
            rawPayload: $body,
        );
    }

    /**
     * Consulta o status oficial de pagamento de uma fatura/link na InfinitePay.
     */
    public function checkPayment(string $orderNsu, string $transactionNsu, string $slug): PaymentCheckResult
    {
        if ($this->isMock()) {
            $cached = Cache::get($this->mockCacheKey($slug));

            if (is_array($cached)) {
                $paid = (bool) ($cached['paid'] ?? false);
                $amount = isset($cached['amount']) ? (int) $cached['amount'] : null;
                $paidAmount = isset($cached['paid_amount']) ? (int) $cached['paid_amount'] : ($paid ? $amount : null);
                $txNsu = ($cached['transaction_nsu'] ?? null) ?: ($transactionNsu !== '' ? $transactionNsu : null);
                $captureMethod = $cached['capture_method'] ?? ($paid ? 'pix' : null);
                $installments = isset($cached['installments']) ? (int) $cached['installments'] : ($paid ? 1 : null);
                $receiptUrl = $cached['receipt_url'] ?? ($paid ? url("/dev/infinitepay/recibo/{$slug}") : null);

                return new PaymentCheckResult(
                    paid: $paid,
                    amountCents: $amount,
                    paidAmountCents: $paidAmount,
                    orderNsu: (string) ($cached['order_nsu'] ?? $orderNsu),
                    transactionNsu: $txNsu,
                    captureMethod: is_string($captureMethod) ? $captureMethod : null,
                    installments: $installments,
                    receiptUrl: is_string($receiptUrl) ? $receiptUrl : null,
                    rawPayload: $cached,
                );
            }

            $paid = $transactionNsu !== '';

            return new PaymentCheckResult(
                paid: $paid,
                amountCents: null,
                paidAmountCents: null,
                orderNsu: $orderNsu,
                transactionNsu: $paid ? $transactionNsu : null,
                captureMethod: $paid ? 'pix' : null,
                installments: $paid ? 1 : null,
                receiptUrl: $paid ? url("/dev/infinitepay/recibo/{$slug}") : null,
                rawPayload: [
                    'paid' => $paid,
                    'order_nsu' => $orderNsu,
                    'transaction_nsu' => $transactionNsu,
                    'slug' => $slug,
                ],
            );
        }

        $this->ensureLiveCredentialsConfigured();

        $payload = [
            'order_nsu' => $orderNsu,
            'transaction_nsu' => $transactionNsu,
            'slug' => $slug,
        ];

        if ($this->handle() !== '') {
            $payload['handle'] = $this->handle();
        }

        $response = $this->sendPost('/payment_check', $payload);
        $body = $this->decodeJson($response);

        return new PaymentCheckResult(
            paid: (bool) ($body['paid'] ?? false),
            amountCents: isset($body['amount']) ? (int) $body['amount'] : null,
            paidAmountCents: isset($body['paid_amount']) ? (int) $body['paid_amount'] : null,
            orderNsu: isset($body['order_nsu']) ? (string) $body['order_nsu'] : $orderNsu,
            transactionNsu: isset($body['transaction_nsu']) ? (string) $body['transaction_nsu'] : ($transactionNsu !== '' ? $transactionNsu : null),
            captureMethod: isset($body['capture_method']) ? (string) $body['capture_method'] : null,
            installments: isset($body['installments']) ? (int) $body['installments'] : null,
            receiptUrl: isset($body['receipt_url']) ? (string) $body['receipt_url'] : null,
            rawPayload: $body,
        );
    }

    protected function httpClient(): PendingRequest
    {
        $client = Http::acceptJson()
            ->asJson()
            ->timeout((int) config('services.infinitepay.timeout', 15));

        if ($this->hasApiKey()) {
            $client = $client->withHeaders([
                'X-Api-Key' => $this->apiKey(),
            ]);
        }

        return $client;
    }

    protected function ensureLiveCredentialsConfigured(): void
    {
        if (! $this->hasApiKey() && $this->handle() === '') {
            throw new InfinitePayException(
                'Configure INFINITEPAY_HANDLE ou INFINITEPAY_API_KEY antes de usar o driver live da InfinitePay.',
            );
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function sendPost(string $endpoint, array $payload): Response
    {
        try {
            $response = $this->httpClient()->post($this->baseUrl().$endpoint, $payload);
        } catch (Throwable $exception) {
            throw new InfinitePayException(
                'Falha de comunicação com a API da InfinitePay: '.$exception->getMessage(),
                previous: $exception,
            );
        }

        if (! $response->successful()) {
            $body = $response->json();
            $bodyArray = is_array($body) ? $body : ['raw_body' => $response->body()];
            $detail = is_array($body)
                ? (string) ($body['message'] ?? $body['error'] ?? '')
                : trim($response->body());

            $message = "A API da InfinitePay retornou HTTP {$response->status()}".($detail !== '' ? ": {$detail}" : '.');

            throw new InfinitePayException(
                $message,
                httpStatus: $response->status(),
                responseBody: $bodyArray,
            );
        }

        return $response;
    }

    /**
     * @return array<string, mixed>
     */
    protected function decodeJson(Response $response): array
    {
        $body = $response->json();

        if (! is_array($body)) {
            throw new InfinitePayException(
                'Resposta inesperada da API da InfinitePay (JSON inválido).',
                httpStatus: $response->status(),
                responseBody: ['raw_body' => $response->body()],
            );
        }

        return $body;
    }
}
