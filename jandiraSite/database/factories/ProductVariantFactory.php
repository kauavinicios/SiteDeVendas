<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $price = $this->faker->randomFloat(2, 20, 300);

        return [
            'product_id' => Product::factory(),
            'name' => $this->faker->randomElement(['Solteiro', 'Casal', 'Queen', 'King', '50 ml', '100 ml']),
            'sku' => strtoupper($this->faker->unique()->bothify('SKU-####-??')),
            'price' => $price,
            'cost_price' => round($price * 0.55, 2),
            'stock' => $this->faker->numberBetween(1, 10),
            'active' => true,
        ];
    }

    public function outOfStock(): static
    {
        return $this->state(fn () => ['stock' => 0]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['active' => false]);
    }
}
