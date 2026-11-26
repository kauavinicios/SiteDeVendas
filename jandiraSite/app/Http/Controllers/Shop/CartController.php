<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Camada HTTP do carrinho. A regra de negócio (sessão, estoque, totais)
 * fica no App\Services\CartService.
 */
class CartController extends Controller
{
    public function __construct(
        private readonly CartService $cart,
    ) {}

    public function index(): View
    {
        $cart = $this->cart->refresh();
        $total = $this->cart->total();

        return view('cart.index', compact('cart', 'total'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $variant = ProductVariant::query()
            ->with('product.images')
            ->where('active', true)
            ->findOrFail($request->variant_id);

        if (! $variant->product->active) {
            abort(404);
        }

        if ($variant->stock <= 0) {
            return back()->with('error', 'Essa opção está indisponível.');
        }

        $this->cart->add($variant, (int) $request->quantity);

        return redirect()
            ->route('cart.index')
            ->with('success', 'Produto adicionado ao carrinho.');
    }

    public function update(Request $request, ProductVariant $variant): JsonResponse
    {
        $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $variant->load('product');

        if (! $this->cart->has($variant->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Item não encontrado no carrinho.',
            ], 404);
        }

        if (! $variant->active || ! $variant->product->active) {
            return response()->json([
                'success' => false,
                'message' => 'Esse produto não está mais disponível.',
            ], 422);
        }

        if ($variant->stock <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Essa opção está indisponível.',
            ], 422);
        }

        // Sempre usa o preço atual do banco.
        $quantity = $this->cart->updateQuantity($variant, (int) $request->quantity);

        return response()->json([
            'success' => true,

            'item' => [
                'variant_id' => $variant->id,
                'quantity' => $quantity,
                'stock' => $variant->stock,
                'subtotal' => (float) ($variant->price * $quantity),
            ],

            'cart' => $this->summary(),
        ]);
    }

    public function destroy(ProductVariant $variant): JsonResponse
    {
        if (! $this->cart->has($variant->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Item não encontrado no carrinho.',
            ], 404);
        }

        $this->cart->remove($variant->id);

        return response()->json([
            'success' => true,

            'removed_variant_id' => $variant->id,

            'cart' => $this->summary(),
        ]);
    }

    public function clear(): JsonResponse
    {
        $this->cart->clear();

        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * @return array{count: int, total: float}
     */
    private function summary(): array
    {
        return [
            'count' => $this->cart->count(),
            'total' => $this->cart->total(),
        ];
    }
}
