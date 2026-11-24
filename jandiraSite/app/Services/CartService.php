<?php

namespace App\Services;

use App\Models\ProductVariant;
use Illuminate\Contracts\Session\Session;

/**
 * Carrinho de compras guardado na sessão.
 *
 * Estrutura: [variant_id => [variant_id, product_id, product_name,
 * product_slug, variant_name, price, quantity, (stock)]]
 */
class CartService
{
    private const SESSION_KEY = 'cart';

    public function __construct(
        private readonly Session $session,
    ) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function items(): array
    {
        return $this->session->get(self::SESSION_KEY, []);
    }

    public function has(int $variantId): bool
    {
        return isset($this->items()[$variantId]);
    }

    /**
     * Sincroniza o carrinho com o banco: atualiza preço/estoque, ajusta
     * quantidades ao estoque atual e remove itens inexistentes ou esgotados.
     *
     * @return array<int, array<string, mixed>>
     */
    public function refresh(): array
    {
        $cart = $this->items();

        if (empty($cart)) {
            return [];
        }

        $variants = ProductVariant::query()
            ->with('product')
            ->whereIn('id', array_keys($cart))
            ->get()
            ->keyBy('id');

        foreach ($cart as $variantId => &$item) {
            $variant = $variants->get($variantId);

            if (! $variant) {
                unset($cart[$variantId]);

                continue;
            }

            $item['stock'] = $variant->stock;
            $item['price'] = (float) $variant->price;

            // Caso o estoque tenha diminuído desde que o produto foi adicionado.
            if ($item['quantity'] > $variant->stock) {
                $item['quantity'] = $variant->stock;
            }
        }

        unset($item);

        // Remove itens que ficaram com estoque zero.
        $cart = collect($cart)
            ->filter(fn ($item) => $item['stock'] > 0)
            ->all();

        $this->save($cart);

        return $cart;
    }

    /**
     * Adiciona a variação (ou soma à quantidade existente), sem passar do estoque.
     */
    public function add(ProductVariant $variant, int $quantity): void
    {
        $variant->loadMissing('product');

        $cart = $this->items();
        $quantity = min($quantity, $variant->stock);

        if (isset($cart[$variant->id])) {
            $cart[$variant->id]['quantity'] = min(
                $cart[$variant->id]['quantity'] + $quantity,
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

        $this->save($cart);
    }

    /**
     * Define a quantidade de um item já existente (limitada ao estoque) e
     * atualiza o preço com o valor atual do banco.
     *
     * @return int Quantidade efetivamente aplicada
     */
    public function updateQuantity(ProductVariant $variant, int $quantity): int
    {
        $cart = $this->items();
        $quantity = min($quantity, $variant->stock);

        $cart[$variant->id]['quantity'] = $quantity;
        $cart[$variant->id]['price'] = (float) $variant->price;

        $this->save($cart);

        return $quantity;
    }

    public function remove(int $variantId): void
    {
        $cart = $this->items();

        unset($cart[$variantId]);

        $this->save($cart);
    }

    public function clear(): void
    {
        $this->session->forget(self::SESSION_KEY);
    }

    public function count(): int
    {
        return (int) collect($this->items())->sum('quantity');
    }

    public function total(): float
    {
        return (float) collect($this->items())
            ->sum(fn ($item) => $item['price'] * $item['quantity']);
    }

    /**
     * @param  array<int, array<string, mixed>>  $cart
     */
    private function save(array $cart): void
    {
        $this->session->put(self::SESSION_KEY, $cart);
    }
}
