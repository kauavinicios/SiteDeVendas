<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\InfinitePayService;
use App\Services\OrderPaymentService;
use App\Services\Payments\InfinitePayException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class InfinitePayWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        string $secret,
        InfinitePayService $infinitePay,
        OrderPaymentService $orderPaymentService,
    ): JsonResponse {
        if (! $infinitePay->isValidWebhookSecret($secret)) {
            return response()->json([
                'ok' => false,
                'message' => 'Invalid webhook secret.',
            ], 403);
        }

        $payload = $request->all();

        $orderNsu = $this->extractString($payload, ['order_nsu', 'data.order_nsu']);
        $slug = $this->extractString($payload, ['slug', 'invoice_slug', 'data.slug', 'data.invoice_slug']);
        $transactionNsu = $this->extractString($payload, ['transaction_nsu', 'data.transaction_nsu']);

        $transaction = $orderPaymentService->findTransactionByIdentifiers(
            orderNsu: $orderNsu,
            slug: $slug,
            transactionNsu: $transactionNsu,
        );

        if ($transaction === null) {
            $orderPaymentService->recordEvent(
                transaction: null,
                order: null,
                source: 'webhook',
                eventType: 'webhook.unmatched',
                payload: $this->summarizeUnmatchedPayload($payload),
                externalEventId: $transactionNsu ?? $slug ?? $orderNsu,
                notes: 'Transação não localizada pelos identificadores enviados no webhook.',
            );

            return response()->json([
                'ok' => false,
                'message' => 'Transaction not found.',
            ], 404);
        }

        $orderPaymentService->recordEvent(
            transaction: $transaction,
            order: $transaction->order,
            source: 'webhook',
            eventType: 'webhook.received',
            payload: $payload,
            externalEventId: $transactionNsu ?? $slug,
        );

        try {
            $synced = $orderPaymentService->verifyAndSyncTransaction(
                transaction: $transaction,
                transactionNsu: $transactionNsu,
                source: 'webhook',
                triggerPayload: $payload,
            );
        } catch (InfinitePayException $exception) {
            $orderPaymentService->recordEvent(
                transaction: $transaction,
                order: $transaction->order,
                source: 'webhook',
                eventType: 'webhook.check_failed',
                payload: [
                    'webhook' => $payload,
                    'response' => $exception->responseBody,
                ],
                externalEventId: $transactionNsu ?? $slug,
                notes: $exception->getMessage(),
                processed: false,
            );

            return response()->json([
                'ok' => false,
                'message' => 'Unable to verify payment right now.',
            ], 502);
        }

        return response()->json([
            'ok' => true,
            'paid' => $synced->isPaid(),
            'status' => $synced->status->value,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  list<string>  $paths
     */
    private function extractString(array $payload, array $paths): ?string
    {
        foreach ($paths as $path) {
            $value = data_get($payload, $path);

            if (is_string($value) || is_numeric($value)) {
                $trimmed = trim((string) $value);

                if ($trimmed !== '') {
                    return $trimmed;
                }
            }
        }

        return null;
    }

    /**
     * Limita e sanitiza o payload salvo para notificações sem transação correspondente.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, string|int|float|bool|null>
     */
    private function summarizeUnmatchedPayload(array $payload): array
    {
        $summary = [];

        foreach (array_slice($payload, 0, 20, true) as $key => $value) {
            $safeKey = Str::limit((string) $key, 60, '');

            if (is_string($value)) {
                $summary[$safeKey] = Str::limit($value, 200);
            } elseif (is_int($value) || is_float($value) || is_bool($value) || $value === null) {
                $summary[$safeKey] = $value;
            } else {
                $summary[$safeKey] = '['.gettype($value).']';
            }
        }

        return $summary;
    }
}
