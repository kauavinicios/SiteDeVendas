<?php

namespace Tests\Feature\Security;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Tests\TestCase;

class AuthUploadAndSearchHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_search_escapes_ilike_wildcards(): void
    {
        Product::factory()->create(['active' => true, 'name' => 'Perfume Rosas']);
        Product::factory()->create(['active' => true, 'name' => 'Kit Desconto 50%']);

        $this->get(route('products.index', ['busca' => '%']))
            ->assertOk()
            ->assertSee('Kit Desconto 50%')
            ->assertDontSee('Perfume Rosas');
    }

    public function test_campaign_cta_url_rejects_non_http_schemes_and_accepts_https(): void
    {
        $manager = User::factory()->manager()->create();

        $this->actingAs($manager)
            ->post(route('admin.campaigns.store'), [
                'subject' => 'Teste',
                'title' => 'Título',
                'body' => 'Corpo da campanha.',
                'cta_label' => 'Abrir',
                'cta_url' => 'javascript:alert(1)',
            ])
            ->assertSessionHasErrors('cta_url');

        $this->actingAs($manager)
            ->post(route('admin.campaigns.store'), [
                'subject' => 'Teste FTP',
                'title' => 'Título FTP',
                'body' => 'Corpo da campanha.',
                'cta_label' => 'Abrir',
                'cta_url' => 'ftp://exemplo.com/arquivo',
            ])
            ->assertSessionHasErrors('cta_url');

        $this->actingAs($manager)
            ->post(route('admin.campaigns.store'), [
                'subject' => 'Campanha Válida',
                'title' => 'Lançamento',
                'body' => 'Confira a coleção nova.',
                'cta_label' => 'Ver catálogo',
                'cta_url' => 'https://butiquedajandira.com.br/produtos',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');
    }

    public function test_product_image_upload_enforces_dimensions_extensions_and_uuid_storage(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();

        $oversized = UploadedFile::fake()->image('gigante.jpg', 4100, 100);

        $this->actingAs($admin)
            ->post(route('admin.product-images.store', $product), [
                'images' => [$oversized],
            ])
            ->assertSessionHasErrors('images.0');

        $invalidExt = UploadedFile::fake()->create('malicioso.php', 10, 'image/jpeg');

        $this->actingAs($admin)
            ->post(route('admin.product-images.store', $product), [
                'images' => [$invalidExt],
            ])
            ->assertSessionHasErrors('images.0');

        $validImage = UploadedFile::fake()->image('nome-original-cliente.jpg', 600, 400);

        $this->actingAs($admin)
            ->post(route('admin.product-images.store', $product), [
                'images' => [$validImage],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $image = ProductImage::where('product_id', $product->id)->firstOrFail();

        $this->assertStringStartsWith("products/{$product->id}/", $image->path);
        $this->assertStringNotContainsString('nome-original-cliente', $image->path);
        Storage::disk('public')->assertExists($image->path);
    }

    public function test_password_defaults_enforce_max_length_and_production_complexity(): void
    {
        $longPassword = str_repeat('a1', 65); // 130 caracteres

        $this->post(route('register'), [
            'name' => 'Cliente Teste',
            'email' => 'senhalonga@exemplo.com',
            'password' => $longPassword,
            'password_confirmation' => $longPassword,
            'terms_accepted' => true,
        ])->assertSessionHasErrors('password');

        Http::fake([
            'api.pwnedpasswords.com/*' => Http::response('', 200),
        ]);

        $originalEnv = $this->app['env'];

        try {
            $this->app['env'] = 'production';

            $onlyNumbers = Validator::make(
                ['password' => '12345678'],
                ['password' => [Password::defaults()]],
            );
            $this->assertTrue($onlyNumbers->fails());

            $onlyLetters = Validator::make(
                ['password' => 'abcdefgh'],
                ['password' => [Password::defaults()]],
            );
            $this->assertTrue($onlyLetters->fails());

            $validProd = Validator::make(
                ['password' => 'Jandira2026'],
                ['password' => [Password::defaults()]],
            );
            $this->assertFalse($validProd->fails());
        } finally {
            $this->app['env'] = $originalEnv;
        }
    }

    public function test_reset_password_endpoint_is_rate_limited(): void
    {
        for ($attempt = 0; $attempt < 6; $attempt++) {
            $this->post(route('password.store'), [
                'token' => 'token-invalido',
                'email' => 'naoexiste@exemplo.com',
                'password' => 'NovaSenha123',
                'password_confirmation' => 'NovaSenha123',
            ]);
        }

        $this->post(route('password.store'), [
            'token' => 'token-invalido',
            'email' => 'naoexiste@exemplo.com',
            'password' => 'NovaSenha123',
            'password_confirmation' => 'NovaSenha123',
        ])->assertStatus(429);
    }
}
