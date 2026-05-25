<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Support\CafeCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_homepage_shows_the_pos_app(): void
    {
        $response = $this
            ->actingAs(User::factory()->create())
            ->get('/');

        $response
            ->assertStatus(200)
            ->assertSee(CafeCatalog::store()['name'])
            ->assertSee('Cafe POS')
            ->assertSee('Kopi Susu Gula Aren')
            ->assertSee('Nota aktif')
            ->assertSee('PPN 11%');
    }

    public function test_checkout_creates_sale_and_decreases_stock(): void
    {
        CafeCatalog::ensure();

        $product = Product::query()->where('sku', 'MLK-002')->firstOrFail();

        $response = $this
            ->actingAs(User::factory()->create())
            ->postJson('/sales', [
            'customer_name' => 'Budi',
            'cashier_name' => CafeCatalog::store()['cashier'],
            'order_type' => 'Dine in',
            'payment_method' => 'Tunai',
            'discount' => 0,
            'paid_amount' => 100000,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2],
            ],
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('sale.customer_name', 'Budi')
            ->assertJsonPath('sale.subtotal', 56000);

        $this->assertDatabaseHas('sales', [
            'customer_name' => 'Budi',
            'subtotal' => 56000,
            'total' => 62160,
        ]);

        $this->assertDatabaseHas('sale_items', [
            'product_id' => $product->id,
            'quantity' => 2,
            'line_total' => 56000,
        ]);

        $this->assertSame(53, $product->fresh()->stock);
        $this->assertSame(1, Sale::query()->count());
    }
}
