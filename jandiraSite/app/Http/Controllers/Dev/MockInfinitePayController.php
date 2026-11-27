<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Models\PaymentTransaction;
use App\Services\InfinitePayService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MockInfinitePayController extends Controller
{
    public function show(string $slug, InfinitePayService $infinitePay): View
    {
        $this->ensureSimulatorEnabled($infinitePay);

        $transaction = PaymentTransaction::query()
            ->with('order.items')
            ->where('slug', $slug)
            ->latest()
            ->first();

        $cached = Cache::get($infinitePay->mockCacheKey($slug));

        abort_if($transaction === null && ! is_array($cached), 404);

        return view('dev.infinitepay-checkout', [
            'slug' => $slug,
            'transaction' => $transaction,
            'order' => $transaction?->order,
            'cached' => is_array($cached) ? $cached : null,
        ]);
    }

    public function simulate(Request $request, string $slug, InfinitePayService $infinitePay): RedirectResponse
    {
        $this->ensureSimulatorEnabled($infinitePay);

        $validated = $request->validate([
            'action' => ['required', 'string', Rule::in(['pay_pix', 'pay_card_1x', 'pay_card_3x', 'cancel'])],
        ]);

        $transaction = PaymentTransaction::query()
            ->where('slug', $slug)
            ->latest()
            ->first();

        $cached = Cache::get($infinitePay->mockCacheKey($slug));

        abort_if($transaction === null && ! is_array($cached), 404);

        $orderNsu = $transaction?->order_nsu ?? (string) ($cached['order_nsu'] ?? $slug);
        $amountCents = $transaction?->amount_cents ?? (int) ($cached['amount'] ?? 0);

        $action = $validated['action'];
        $existingCache = is_array($cached) ? $cached : [];
        $returnToken = $this->extractReturnToken((string) ($existingCache['redirect_url'] ?? ''));

        if ($action === 'cancel') {
            Cache::put($infinitePay->mockCacheKey($slug), array_merge($existingCache, [
                'slug' => $slug,
                'order_nsu' => $orderNsu,
                'amount' => $amountCents,
                'paid' => false,
                'paid_amount' => null,
                'transaction_nsu' => null,
                'capture_method' => null,
                'installments' => null,
                'receipt_url' => null,
            ]), now()->addHours(6));

            return redirect()->route('checkout.return', array_filter([
                'order_nsu' => $orderNsu,
                'slug' => $slug,
                'rt' => $returnToken,
            ]));
        }

        [$captureMethod, $installments] = match ($action) {
            'pay_card_1x' => ['credit_card', 1],
            'pay_card_3x' => ['credit_card', 3],
            default => ['pix', 1],
        };

        $transactionNsu = 'mock-tx-'.Str::uuid()->toString();
        $receiptUrl = route('dev.infinitepay.receipt', ['slug' => $slug]);

        Cache::put($infinitePay->mockCacheKey($slug), array_merge($existingCache, [
            'slug' => $slug,
            'order_nsu' => $orderNsu,
            'amount' => $amountCents,
            'paid' => true,
            'paid_amount' => $amountCents,
            'transaction_nsu' => $transactionNsu,
            'capture_method' => $captureMethod,
            'installments' => $installments,
            'receipt_url' => $receiptUrl,
        ]), now()->addHours(6));

        return redirect()->route('checkout.return', array_filter([
            'order_nsu' => $orderNsu,
            'slug' => $slug,
            'transaction_nsu' => $transactionNsu,
            'capture_method' => $captureMethod,
            'receipt_url' => $receiptUrl,
            'rt' => $returnToken,
        ]));
    }

    public function receipt(string $slug, InfinitePayService $infinitePay): View
    {
        $this->ensureSimulatorEnabled($infinitePay);

        $transaction = PaymentTransaction::query()
            ->with('order.items')
            ->where('slug', $slug)
            ->latest()
            ->first();

        $cached = Cache::get($infinitePay->mockCacheKey($slug));

        abort_if($transaction === null && ! is_array($cached), 404);

        return view('dev.infinitepay-receipt', [
            'slug' => $slug,
            'transaction' => $transaction,
            'order' => $transaction?->order,
            'cached' => is_array($cached) ? $cached : null,
        ]);
    }

    private function ensureSimulatorEnabled(InfinitePayService $infinitePay): void
    {
        abort_unless(app()->environment(['local', 'testing']) && $infinitePay->isMock(), 404);
    }

    private function extractReturnToken(string $redirectUrl): ?string
    {
        if ($redirectUrl === '') {
            return null;
        }

        $queryString = (string) parse_url($redirectUrl, PHP_URL_QUERY);

        if ($queryString === '') {
            return null;
        }

        parse_str($queryString, $query);

        $token = $query['rt'] ?? null;

        return is_string($token) && trim($token) !== '' ? trim($token) : null;
    }
}
