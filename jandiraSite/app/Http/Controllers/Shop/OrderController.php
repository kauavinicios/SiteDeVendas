<?php

namespace App\Http\Controllers\Shop;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Services\CartService;
use App\Services\InfinitePayService;
use App\Services\OrderPaymentService;
use App\Services\Payments\InfinitePayException;
use App\Support\EmailValidation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function store(
        Request $request,
        CartService $cart,
        OrderPaymentService $orderPaymentService,
        InfinitePayService $infinitePay,
    ): JsonResponse {
        if ($request->has('customer_email')) {
            $email = Str::lower(trim((string) $request->input('customer_email')));
            $request->merge([
                'customer_email' => $email !== '' ? $email : null,
            ]);
        }

        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:30'],
            'customer_email' => ['nullable', 'string', 'lowercase', EmailValidation::rule(), 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'customer_name.required' => 'Informe seu nome completo para registrar o pedido.',
            'customer_phone.required' => 'Informe seu telefone ou WhatsApp para contato.',
            'customer_email.email' => 'Informe um e-mail válido.',
            'notes.max' => 'As observações podem ter no máximo 1000 caracteres.',
        ]);

        $cartItems = $cart->refresh();

        if (empty($cartItems)) {
            return response()->json([
                'success' => false,
                'message' => 'Seu carrinho está vazio ou os itens ficaram indisponíveis.',
            ], 422);
        }

        $order = DB::transaction(function () use ($request, $validated, $cartItems): Order {
            $variants = ProductVariant::query()
                ->with('product')
                ->whereIn('id', array_keys($cartItems))
                ->get()
                ->keyBy('id');

            $total = 0.0;
            $totalCost = 0.0;
            $hasAnyCost = false;
            $itemsCount = 0;
            $itemsPayload = [];

            foreach ($cartItems as $variantId => $item) {
                $variant = $variants->get($variantId);

                if (! $variant || ! $variant->active || ! $variant->product || ! $variant->product->active) {
                    throw ValidationException::withMessages([
                        'cart' => "O item \"{$item['product_name']}\" não está mais disponível.",
                    ]);
                }

                $quantity = (int) $item['quantity'];

                if ($variant->stock < $quantity || $quantity <= 0) {
                    throw ValidationException::withMessages([
                        'cart' => "Estoque insuficiente para \"{$variant->product->name} — {$variant->name}\".",
                    ]);
                }

                $unitPrice = round((float) $variant->price, 2);
                $unitCost = $variant->cost_price !== null ? round((float) $variant->cost_price, 2) : null;
                $subtotal = round($unitPrice * $quantity, 2);

                $total += $subtotal;
                $itemsCount += $quantity;

                if ($unitCost !== null) {
                    $hasAnyCost = true;
                    $totalCost += round($unitCost * $quantity, 2);
                }

                $itemsPayload[] = [
                    'product_id' => $variant->product_id,
                    'product_variant_id' => $variant->id,
                    'product_name' => $variant->product->name,
                    'variant_name' => $variant->name,
                    'sku' => $variant->sku,
                    'unit_price' => $unitPrice,
                    'unit_cost' => $unitCost,
                    'quantity' => $quantity,
                    'subtotal' => $subtotal,
                ];
            }

            $order = Order::create([
                'code' => Order::generateCode(),
                'user_id' => $request->user()?->id,
                'customer_name' => trim($validated['customer_name']),
                'customer_email' => $validated['customer_email'] ?? null,
                'customer_phone' => trim($validated['customer_phone']),
                'status' => OrderStatus::Pending,
                'channel' => 'infinitepay',
                'payment_method' => null,
                'payment_status' => 'pending',
                'payment_reference' => null,
                'total' => round($total, 2),
                'total_cost' => $hasAnyCost ? round($totalCost, 2) : null,
                'items_count' => $itemsCount,
                'notes' => filled($validated['notes'] ?? null) ? trim($validated['notes']) : null,
            ]);

            $order->items()->createMany($itemsPayload);

            return $order->load('items');
        });

        $redirectUrl = route('checkout.return');
        $webhookSecret = $infinitePay->webhookSecret();
        $webhookUrl = $webhookSecret !== ''
            ? route('webhooks.infinitepay', ['secret' => $webhookSecret])
            : null;

        try {
            $transaction = $orderPaymentService->initiateInfinitePayCheckout(
                order: $order,
                redirectUrl: $redirectUrl,
                webhookUrl: $webhookUrl,
            );
        } catch (InfinitePayException $exception) {
            Log::warning('Falha ao iniciar checkout InfinitePay', [
                'order_id' => $order->id,
                'order_code' => $order->code,
                'http_status' => $exception->httpStatus,
                'message' => $exception->getMessage(),
                'response' => $exception->responseBody,
            ]);

            $order->delete();

            return response()->json([
                'success' => false,
                'message' => 'Não foi possível iniciar o pagamento pela InfinitePay no momento. Tente novamente em instantes.',
            ], 502);
        }

        $cart->clear();

        return response()->json([
            'success' => true,
            'message' => "Pedido {$order->code} registrado! Redirecionando para o pagamento seguro...",
            'order' => [
                'code' => $order->code,
                'total' => (float) $order->total,
                'status_label' => $order->status->label(),
            ],
            'checkout_url' => $transaction->checkout_url,
            'payment' => [
                'provider' => $transaction->provider,
                'driver' => $transaction->driver,
                'slug' => $transaction->slug,
                'status' => $transaction->status->value,
            ],
            'whatsapp_url' => $this->buildWhatsAppUrl($order),
        ], 201);
    }

    private function buildWhatsAppUrl(Order $order): ?string
    {
        $number = preg_replace('/\D+/', '', (string) config('services.whatsapp.number'));

        if ($number === '') {
            return null;
        }

        $lines = [
            "Olá! Gostaria de confirmar meu pedido *#{$order->code}* na Butique da Jandira.",
            '',
            "Cliente: {$order->customer_name}",
            "Telefone: {$order->customer_phone}",
            '',
            'Itens do pedido:',
        ];

        foreach ($order->items as $index => $item) {
            $position = $index + 1;
            $unitFormatted = number_format((float) $item->unit_price, 2, ',', '.');
            $subtotalFormatted = number_format((float) $item->subtotal, 2, ',', '.');

            $lines[] = "{$position}. {$item->product_name} ({$item->variant_name})";
            $lines[] = "   Qtd: {$item->quantity} x R$ {$unitFormatted} = R$ {$subtotalFormatted}";
        }

        $totalFormatted = number_format((float) $order->total, 2, ',', '.');

        $lines[] = '';
        $lines[] = "Total do pedido: R$ {$totalFormatted}";

        if ($order->notes) {
            $lines[] = '';
            $lines[] = "Observações: {$order->notes}";
        }

        $lines[] = '';
        $lines[] = 'Gostaria de confirmar a disponibilidade e combinar o pagamento e a entrega.';

        return 'https://wa.me/'.$number.'?text='.rawurlencode(implode("\n", $lines));
    }
}
