<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_nonexistent_route_renders_custom_404_page(): void
    {
        $response = $this->get('/pagina-que-nao-existe');

        $response
            ->assertNotFound()
            ->assertSee('404')
            ->assertSee('Ops! Não encontramos essa página.')
            ->assertSee(url('/'));
    }

    public function test_nonexistent_product_renders_custom_404_page(): void
    {
        $response = $this->get('/produtos/produto-inexistente');

        $response
            ->assertNotFound()
            ->assertSee('404')
            ->assertSee('Voltar para a página inicial');
    }

    public function test_customer_accessing_admin_renders_custom_403_page(): void
    {
        $customer = User::factory()->customer()->create();

        $response = $this->actingAs($customer)->get('/admin/dashboard');

        $response
            ->assertForbidden()
            ->assertSee('403')
            ->assertSee('Você não tem permissão para acessar esta área.')
            ->assertSee(url('/'));
    }
}
