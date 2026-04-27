<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_navbar_shows_cart_icon_with_item_count(): void
    {
        $this
            ->withSession([
                'cart' => [
                    ['id' => 'cart-one', 'quantity' => 2],
                    ['id' => 'cart-two', 'quantity' => 3],
                ],
            ])
            ->get(route('home'))
            ->assertOk()
            ->assertSee('cart-icon-link', false)
            ->assertSee('aria-label="Carrello, 5 articoli"', false)
            ->assertSee('<span class="cart-icon-count">5</span>', false);
    }
}
