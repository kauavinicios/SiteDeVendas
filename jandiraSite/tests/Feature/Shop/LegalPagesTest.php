<?php

namespace Tests\Feature\Shop;

use App\Support\LegalDocuments;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_terms_of_use_page_can_be_rendered(): void
    {
        $this->get(route('legal.terms'))
            ->assertOk()
            ->assertSee('Termos de Uso')
            ->assertSee('Butique da Jandira')
            ->assertSee(LegalDocuments::TERMS_VERSION);
    }

    public function test_privacy_policy_page_can_be_rendered(): void
    {
        $this->get(route('legal.privacy'))
            ->assertOk()
            ->assertSee('Política de Privacidade')
            ->assertSee('LGPD')
            ->assertSee(LegalDocuments::PRIVACY_VERSION);
    }
}
