<?php

namespace App\Http\Controllers;

use App\Models\ProductVariant;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index()
    {
        $cart = session()->get('cart', []);

        if (empty($cart)) {
            return view('cart.index', [
                'cart' => [],
                'total' => 0,
            ]);
        }

        $variantIds = array_keys($cart);

        $variants = ProductVariant::query()
            ->with('product')
            ->whereIn('id', $variantIds)
            ->get()
            ->keyBy('id');

        foreach ($cart as $variantId => &$item) {

            $variant = $variants->get($variantId);

            if (!$variant) {
                unset($cart[$variantId]);
                continue;
            }

            $item['stock'] = $variant->stock;
            $item['price'] = (float) $variant->price;

            /*
            * Caso o estoque tenha diminuído desde que
            * o produto foi adicionado ao carrinho.
            */
            if ($item['quantity'] > $variant->stock) {
                $item['quantity'] = $variant->stock;
            }
        }

        unset($item);

        /*
        * Remove itens que ficaram com estoque zero.
        */
        $cart = collect($cart)
            ->filter(fn ($item) => $item['stock'] > 0)
            ->all();

        session()->put('cart', $cart);

        $total = collect($cart)->sum(function ($item) {
            return $item['price'] * $item['quantity'];
        });

        return view('cart.index', compact('cart', 'total'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $variant = ProductVariant::query()
            ->with('product')
            ->where('active', true)
            ->findOrFail($request->variant_id);

        if (!$variant->product->active) {
            abort(404);
        }

        if ($variant->stock <= 0) {
            return back()->with('error', 'Essa opção está indisponível.');
        }

        $quantity = min(
            (int) $request->quantity,
            $variant->stock
        );

        $cart = session()->get('cart', []);

        if (isset($cart[$variant->id])) {
            $newQuantity = $cart[$variant->id]['quantity'] + $quantity;

            $cart[$variant->id]['quantity'] = min(
                $newQuantity,
                $variant->stock
            );
        } else {
            $cart[$variant->id] = [
                'variant_id' => $variant->id,
                'product_id' => $variant->product->id,
                'product_name' => $variant->product->name,
                'product_slug' => $variant->product->slug,
                'variant_name' => $variant->name,
                'price' => (float) $variant->price,
                'quantity' => $quantity,
            ];
        }

        session()->put('cart', $cart);

        return redirect()
            ->route('cart.index')
            ->with('success', 'Produto adicionado ao carrinho.');
    }

    public function update(Request $request, ProductVariant $variant)
    {
        $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $variant->load('product');
        $cart = session()->get('cart', []);

        if (!isset($cart[$variant->id])) {
            return response()->json([
                'success' => false,
                'message' => 'Item não encontrado no carrinho.',
            ], 404);
        }

        if (!$variant->active || !$variant->product->active) {
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

        $quantity = min(
            (int) $request->quantity,
            $variant->stock
        );

        $cart[$variant->id]['quantity'] = $quantity;

        // Sempre usa o preço atual do banco.
        $cart[$variant->id]['price'] = (float) $variant->price;

        session()->put('cart', $cart);

        $subtotal = $variant->price * $quantity;

        $cartCount = collect($cart)->sum('quantity');

        $cartTotal = collect($cart)->sum(function ($item) {
            return $item['price'] * $item['quantity'];
        });

        return response()->json([
            'success' => true,

            'item' => [
                'variant_id' => $variant->id,
                'quantity' => $quantity,
                'stock' => $variant->stock,
                'subtotal' => (float) $subtotal,
            ],

            'cart' => [
                'count' => $cartCount,
                'total' => (float) $cartTotal,
            ],
        ]);
    }

    public function destroy(ProductVariant $variant)
    {
        $cart = session()->get('cart', []);

        if (!isset($cart[$variant->id])) {
            return response()->json([
                'success' => false,
                'message' => 'Item não encontrado no carrinho.',
            ], 404);
        }

        unset($cart[$variant->id]);

        session()->put('cart', $cart);

        $cartCount = collect($cart)->sum('quantity');

        $cartTotal = collect($cart)->sum(function ($item) {
            return $item['price'] * $item['quantity'];
        });

        return response()->json([
            'success' => true,

            'removed_variant_id' => $variant->id,

            'cart' => [
                'count' => $cartCount,
                'total' => (float) $cartTotal,
            ],
        ]);
    }

    public function clear()
    {
        session()->forget('cart');

        return response()->json([
            'success' => true,
        ]);
    }
}