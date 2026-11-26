<?php

namespace Tests\Feature\Shop;

use App\Mail\BackInStockMail;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockAlert;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class FavoriteAndStockAlertTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_toggle_favorite_and_view_it_in_my_account(): void
    {
        $customer = User::factory()->customer()->create();
        $product = Product::factory()->create(['active' => true, 'name' => 'Perfume Jasmim']);
        ProductVariant::factory()->for($product)->create(['price' => 189.90, 'stock' => 3]);

        $this->actingAs($customer)
            ->post(route('favorites.toggle', $product))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('favorites', [
            'user_id' => $customer->id,
            'product_id' => $product->id,
        ]);

        $this->actingAs($customer)
            ->get(route('account.index'))
            ->assertOk()
            ->assertSee('Meus preferidos')
            ->assertSee('Perfume Jasmim');

        // Alterna novamente para remover
        $this->actingAs($customer)
            ->post(route('favorites.toggle', $product))
            ->assertRedirect();

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $customer->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_guest_and_customer_can_subscribe_to_out_of_stock_alert(): void
    {
        $product = Product::factory()->create(['active' => true]);
        $outOfStock = ProductVariant::factory()->for($product)->outOfStock()->create(['active' => true]);
        $inStock = ProductVariant::factory()->for($product)->create(['stock' => 4, 'active' => true]);

        // Não permite alerta se já tiver estoque
        $this->post(route('stock-alerts.store'), [
            'variant_id' => $inStock->id,
            'email' => 'visitante@example.com',
        ])->assertSessionHas('error');

        // Permite visitante na variação esgotada (sem duplicar)
        $this->post(route('stock-alerts.store'), [
            'variant_id' => $outOfStock->id,
            'email' => 'Visitante@Example.com',
        ])->assertSessionHas('success');

        $this->post(route('stock-alerts.store'), [
            'variant_id' => $outOfStock->id,
            'email' => 'visitante@example.com',
        ])->assertSessionHas('success');

        $this->assertDatabaseCount('stock_alerts', 1);
        $this->assertDatabaseHas('stock_alerts', [
            'product_variant_id' => $outOfStock->id,
            'email' => 'visitante@example.com',
            'notified_at' => null,
        ]);
    }

    public function test_restocking_variant_queues_branded_email_without_duplicates_and_skips_if_already_in_stock(): void
    {
        Mail::fake();

        $storekeeper = User::factory()->storekeeper()->create();
        $product = Product::factory()->create(['active' => true, 'name' => 'Cobre-leito Imperial']);
        $variant = ProductVariant::factory()->for($product)->outOfStock()->create([
            'name' => 'King',
            'price' => 299.90,
            'active' => true,
        ]);

        $favCustomer = User::factory()->customer()->create(['email' => 'favorito@example.com']);
        $favCustomer->favorites()->create([
            'product_id' => $product->id,
            'notify_on_restock' => true,
        ]);

        // Mesmo cliente também pediu alerta explícito — deve receber apenas 1 e-mail
        $alertSameUser = StockAlert::create([
            'product_variant_id' => $variant->id,
            'user_id' => $favCustomer->id,
            'email' => 'favorito@example.com',
        ]);

        $alertGuest = StockAlert::create([
            'product_variant_id' => $variant->id,
            'email' => 'aviso@example.com',
        ]);

        $this->actingAs($storekeeper)
            ->patch(route('admin.stock.update', $variant), [
                'stock' => 5,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        Mail::assertQueued(BackInStockMail::class, 2);
        Mail::assertQueued(BackInStockMail::class, function (BackInStockMail $mail) {
            $rendered = $mail->render();

            return $mail->hasTo('favorito@example.com')
                && str_contains($rendered, 'Butique da Jandira')
                && str_contains($rendered, 'Cobre-leito Imperial');
        });
        Mail::assertQueued(BackInStockMail::class, fn (BackInStockMail $mail) => $mail->hasTo('aviso@example.com'));

        $this->assertNotNull($alertSameUser->fresh()->notified_at);
        $this->assertNotNull($alertGuest->fresh()->notified_at);

        // Atualizar de 5 -> 8 NÃO deve reenviar notificações
        Mail::fake();

        $this->actingAs($storekeeper)
            ->patch(route('admin.stock.update', $variant), [
                'stock' => 8,
            ])
            ->assertRedirect();

        Mail::assertNothingQueued();
    }
}
