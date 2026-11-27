<?php

namespace App\Http\Controllers\Shop;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\PaymentTransaction;
use App\Services\OrderPaymentService;
use App\Services\Payments\InfinitePayException;
use App\Support\SecurityAudit;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CheckoutReturnController extends Controller
{
    public function __invoke(Request $request, OrderPaymentService $orderPaymentService): View
    {
        $orderNsu = trim($request->string('order_nsu')->toString());
        $slug = trim($request->string('slug')->toString()) ?: trim($request->string('invoice_slug')->toString());
        $transactionNsu = trim($request->string('transaction_nsu')->toString());

        $transaction = $orderPaymentService->findTransactionByIdentifiers(
            orderNsu: $orderNsu !== '' ? $orderNsu : null,
            slug: $slug !== '' ? $slug : null,
            transactionNsu: $transactionNsu !== '' ? $transactionNsu : null,
        );

        if ($transaction === null || ! $this->canAccessTransaction($request, $transaction)) {
            if ($transaction !== null) {
                SecurityAudit::record('checkout.return_unauthorized', [
                    'transaction_id' => $transaction->id,
                ], 'warning');
            }

            return view('checkout.return', [
                'transaction' => null,
                'order' => null,
                'verificationWarning' => null,
            ]);
        }

        $verificationWarning = null;

        try {
            $transaction = $orderPaymentService->verifyAndSyncTransaction(
                transaction: $transaction,
                transactionNsu: $transactionNsu !== '' ? $transactionNsu : null,
                source: 'redirect_return',
                triggerPayload: $request->query(),
            );
        } catch (InfinitePayException $exception) {
            $orderPaymentService->recordEvent(
                transaction: $transaction,
                order: $transaction->order,
                source: 'redirect_return',
                eventType: 'payment.check_failed',
                payload: [
                    'query' => $request->query(),
                    'response' => $exception->responseBody,
                ],
                externalEventId: $transactionNsu !== '' ? $transactionNsu : $transaction->slug,
                notes: $exception->getMessage(),
            );

            $verificationWarning = 'Ainda estamos aguardando a confirmação da InfinitePay. Atualize em alguns instantes.';
        }

        $transaction->load(['order.items']);

        return view('checkout.return', [
            'transaction' => $transaction,
            'order' => $transaction->order,
            'verificationWarning' => $verificationWarning,
        ]);
    }

    private function canAccessTransaction(Request $request, PaymentTransaction $transaction): bool
    {
        $transaction->loadMissing('order');
        $user = $request->user();
        $order = $transaction->order;

        if ($user !== null) {
            if ($order !== null && $order->user_id !== null && $user->id === $order->user_id) {
                return true;
            }

            if ($user->hasPermission(Permission::ViewOrders)) {
                return true;
            }
        }

        if ($transaction->matchesReturnToken($request->string('rt')->toString())) {
            return true;
        }

        $sessionSlugs = array_map(
            'strval',
            (array) $request->session()->get('checkout_transaction_slugs', []),
        );

        return $transaction->slug !== null && in_array($transaction->slug, $sessionSlugs, true);
    }
}
