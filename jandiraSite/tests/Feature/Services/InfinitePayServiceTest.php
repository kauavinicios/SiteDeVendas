<?php

namespace Tests\Feature\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\InfinitePayService;
use App\Services\Payments\InfinitePayException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class InfinitePayServiceTest extends TestCase
{
    use RefreshDatabase;

    private function createSampleOrder(): Order
    {
        $order = Order::create([
            'code' => 'BJ-20261126-TEST1',
            'customer_name' => 'Maria Oliveira',
            'customer_email' => 'maria@exemplo.com',
            'customer_phone' => '(67) 99988-7766',
            'status' => OrderStatus::Pending,
            'channel' => 'infinitepay',
            'payment_status' => 'pending',
            'total' => 249.90,
            'items_count' => 3,
        ]);

        $order->items()->createMany([
            [
                'product_name' => 'Jogo de Lençol Casal',
                'variant_name' => 'Algodão 200 Fios - Rosê',
                'sku' => 'LEN-CAS-01',
                'unit_price' => 149.90,
                'quantity' => 1,
                'subtotal' => 149.90,
            ],
            [
                'product_name' => 'Perfume Floral',
                'variant_name' => '100ml',
                'sku' => 'PER-FLO-100',
                'unit_price' => 50.00,
                'quantity' => 2,
                'subtotal' => 100.00,
            ],
        ]);

        return $order->load('items');
    }

    public function test_mock_driver_creates_checkout_link_and_reads_cached_payment_state(): void
    {
        config()->set('services.infinitepay.driver', 'mock');

        $order = $this->createSampleOrder();
        $service = app(InfinitePayService::class);

        $link = $service->createCheckoutLink(
            order: $order,
            redirectUrl: 'https://loja.test/checkout/retorno',
            webhookUrl: 'https://loja.test/webhooks/infinitepay/secret',
        );

        $this->assertSame('mock', $link->driver);
        $this->assertSame('mock', $link->authMode);
        $this->assertSame(24990, $link->amountCents);
        $this->assertSame('BJ-20261126-TEST1', $link->orderNsu);
        $this->assertStringStartsWith('mock-', $link->slug);
        $this->assertStringContainsString("/dev/infinitepay/checkout/{$link->slug}", $link->url);

        $unpaidCheck = $service->checkPayment($order->code, '', $link->slug);
        $this->assertFalse($unpaidCheck->paid);
        $this->assertSame(24990, $unpaidCheck->amountCents);

        $cached = Cache::get($service->mockCacheKey($link->slug));
        $this->assertIsArray($cached);

        Cache::put($service->mockCacheKey($link->slug), array_merge($cached, [
            'paid' => true,
            'paid_amount' => 24990,
            'transaction_nsu' => 'tx-mock-123',
            'capture_method' => 'credit_card',
            'installments' => 3,
            'receipt_url' => 'https://loja.test/dev/infinitepay/recibo/'.$link->slug,
        ]), now()->addHour());

        $paidCheck = $service->checkPayment($order->code, 'tx-mock-123', $link->slug);
        $this->assertTrue($paidCheck->paid);
        $this->assertSame(24990, $paidCheck->paidAmountCents);
        $this->assertSame('credit_card', $paidCheck->captureMethod);
        $this->assertSame(3, $paidCheck->installments);
        $this->assertSame('tx-mock-123', $paidCheck->transactionNsu);
    }

    public function test_live_driver_without_api_key_uses_handle_and_omits_options_and_header(): void
    {
        config()->set('services.infinitepay.driver', 'live');
        config()->set('services.infinitepay.base_url', 'https://api.checkout.infinitepay.io');
        config()->set('services.infinitepay.handle', '$butiquedajandira');
        config()->set('services.infinitepay.api_key', '');

        Http::fake([
            'https://api.checkout.infinitepay.io/links' => Http::response([
                'success' => true,
                'url' => 'https://pay.infinitepay.io/slug-handle-123',
                'slug' => 'slug-handle-123',
                'order_nsu' => 'BJ-20261126-TEST1',
                'amount' => 24990,
            ], 200),
            'https://api.checkout.infinitepay.io/payment_check' => Http::response([
                'paid' => true,
                'amount' => 24990,
                'paid_amount' => 24990,
                'order_nsu' => 'BJ-20261126-TEST1',
                'transaction_nsu' => '8f9e7d6c-1111-2222-3333-444455556666',
                'capture_method' => 'pix',
                'installments' => 1,
                'receipt_url' => 'https://pay.infinitepay.io/receipt/123',
            ], 200),
        ]);

        $order = $this->createSampleOrder();
        $service = app(InfinitePayService::class);

        $this->assertSame('butiquedajandira', $service->handle());
        $this->assertFalse($service->hasApiKey());
        $this->assertSame('handle', $service->authMode());

        $link = $service->createCheckoutLink(
            order: $order,
            redirectUrl: 'https://loja.test/checkout/retorno',
            webhookUrl: 'https://loja.test/webhooks/infinitepay/segredo',
        );

        $this->assertSame('live', $link->driver);
        $this->assertSame('handle', $link->authMode);
        $this->assertSame('slug-handle-123', $link->slug);
        $this->assertSame(24990, $link->amountCents);

        Http::assertSent(function (Request $request): bool {
            if ($request->url() !== 'https://api.checkout.infinitepay.io/links') {
                return false;
            }

            $data = $request->data();

            return ! $request->hasHeader('X-Api-Key')
                && ($data['handle'] ?? null) === 'butiquedajandira'
                && ($data['order_nsu'] ?? null) === 'BJ-20261126-TEST1'
                && ! array_key_exists('options', $data)
                && ($data['customer']['phoneNumber'] ?? null) === '+5567999887766'
                && ($data['items'][0]['price'] ?? null) === 14990
                && ($data['items'][1]['price'] ?? null) === 5000
                && ($data['items'][1]['quantity'] ?? null) === 2;
        });

        $check = $service->checkPayment(
            orderNsu: 'BJ-20261126-TEST1',
            transactionNsu: '8f9e7d6c-1111-2222-3333-444455556666',
            slug: 'slug-handle-123',
        );

        $this->assertTrue($check->paid);
        $this->assertSame(24990, $check->paidAmountCents);
        $this->assertSame('pix', $check->captureMethod);
        $this->assertSame(1, $check->installments);
        $this->assertSame('https://pay.infinitepay.io/receipt/123', $check->receiptUrl);

        Http::assertSent(function (Request $request): bool {
            if ($request->url() !== 'https://api.checkout.infinitepay.io/payment_check') {
                return false;
            }

            $data = $request->data();

            return ! $request->hasHeader('X-Api-Key')
                && ($data['handle'] ?? null) === 'butiquedajandira'
                && ($data['slug'] ?? null) === 'slug-handle-123';
        });
    }

    public function test_live_driver_with_api_key_sends_header_and_options(): void
    {
        config()->set('services.infinitepay.driver', 'live');
        config()->set('services.infinitepay.base_url', 'https://api.checkout.infinitepay.io');
        config()->set('services.infinitepay.handle', 'butiquedajandira');
        config()->set('services.infinitepay.api_key', 'inchk_secret_test_key');
        config()->set('services.infinitepay.single_payment', true);
        config()->set('services.infinitepay.require_delivery_address', false);

        Http::fake([
            'https://api.checkout.infinitepay.io/links' => Http::response([
                'success' => true,
                'url' => 'https://pay.infinitepay.io/slug-key-999',
                'slug' => 'slug-key-999',
                'order_nsu' => 'BJ-20261126-TEST1',
                'amount' => 24990,
            ], 200),
            'https://api.checkout.infinitepay.io/payment_check' => Http::response([
                'paid' => false,
                'order_nsu' => 'BJ-20261126-TEST1',
            ], 200),
        ]);

        $order = $this->createSampleOrder();
        $service = app(InfinitePayService::class);

        $this->assertTrue($service->hasApiKey());
        $this->assertSame('api_key', $service->authMode());

        $link = $service->createCheckoutLink($order);
        $this->assertSame('api_key', $link->authMode);

        Http::assertSent(function (Request $request): bool {
            if ($request->url() !== 'https://api.checkout.infinitepay.io/links') {
                return false;
            }

            $data = $request->data();

            return ($request->header('X-Api-Key')[0] ?? null) === 'inchk_secret_test_key'
                && isset($data['options'])
                && $data['options']['single_payment'] === true
                && $data['options']['require_delivery_address'] === false;
        });

        $check = $service->checkPayment('BJ-20261126-TEST1', 'tx-1', 'slug-key-999');
        $this->assertFalse($check->paid);

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://api.checkout.infinitepay.io/payment_check'
                && ($request->header('X-Api-Key')[0] ?? null) === 'inchk_secret_test_key';
        });
    }

    public function test_live_driver_validation_http_errors_and_webhook_secret_checks(): void
    {
        config()->set('services.infinitepay.driver', 'live');
        config()->set('services.infinitepay.handle', '');
        config()->set('services.infinitepay.api_key', '');
        config()->set('services.infinitepay.webhook_secret', 'segredo-super-forte');

        $order = $this->createSampleOrder();
        $service = app(InfinitePayService::class);

        $this->assertTrue($service->isValidWebhookSecret('segredo-super-forte'));
        $this->assertFalse($service->isValidWebhookSecret('segredo-errado'));
        $this->assertFalse($service->isValidWebhookSecret(''));

        config()->set('services.infinitepay.webhook_secret', '');
        $this->assertFalse($service->isValidWebhookSecret('qualquer'));

        try {
            $service->createCheckoutLink($order);
            $this->fail('Era esperada InfinitePayException quando handle e api_key estão vazios.');
        } catch (InfinitePayException $exception) {
            $this->assertStringContainsString('INFINITEPAY_HANDLE', $exception->getMessage());
        }

        config()->set('services.infinitepay.handle', 'butiquedajandira');

        Http::fake([
            'https://api.checkout.infinitepay.io/links' => Http::response([
                'message' => 'Parâmetro handle inválido.',
            ], 422),
        ]);

        try {
            $service->createCheckoutLink($order);
            $this->fail('Era esperada InfinitePayException em HTTP 422.');
        } catch (InfinitePayException $exception) {
            $this->assertSame(422, $exception->httpStatus);
            $this->assertSame(['message' => 'Parâmetro handle inválido.'], $exception->responseBody);
        }
    }
}
