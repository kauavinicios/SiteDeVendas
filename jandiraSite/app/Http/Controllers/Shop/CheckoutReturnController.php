<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Services\OrderPaymentService;
use App\Services\Payments\InfinitePayException;
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

        $verificationWarning = null;

        if ($transaction !== null) {
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
        }

        return view('checkout.return', [
            'transaction' => $transaction,
            'order' => $transaction?->order,
            'verificationWarning' => $verificationWarning,
        ]);
    }
}
