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

    public function test_dashboard_shows_operational_summary(): void
    {
        $response = $this
            ->actingAs(User::factory()->admin()->create())
            ->get(route('dashboard.index'));

        $response
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertSee('Open Bill Meja')
            ->assertSee('Stok Menipis')
            ->assertSee('QR Meja');
    }

    public function test_checkout_creates_sale_and_decreases_stock(): void
    {
        CafeCatalog::ensure();

        $product = Product::query()->where('sku', 'MLK-002')->firstOrFail();

        $response = $this
            ->actingAs(User::factory()->create())
            ->postJson('/sales', [
            'customer_name' => 'Budi',
            'table_number' => '1',
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
            ->assertJsonPath('sale.table_number', '1')
            ->assertJsonPath('sale.subtotal', 56000);

        $this->assertDatabaseHas('sales', [
            'customer_name' => 'Budi',
            'table_number' => '1',
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

    public function test_checkout_accepts_non_cash_payment_reference(): void
    {
        CafeCatalog::ensure();

        $product = Product::query()->where('sku', 'ESP-001')->firstOrFail();

        $this
            ->actingAs(User::factory()->create())
            ->postJson('/sales', [
                'customer_name' => 'Sari',
                'cashier_name' => CafeCatalog::store()['cashier'],
                'order_type' => 'Dine in',
                'payment_method' => 'QRIS',
                'payment_reference' => 'QRIS-123456',
                'discount' => 0,
                'paid_amount' => 19980,
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 1],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('sale.payment_method', 'QRIS')
            ->assertJsonPath('sale.payment_reference', 'QRIS-123456')
            ->assertJsonPath('sale.total', 19980)
            ->assertJsonPath('sale.paid_amount', 19980)
            ->assertJsonPath('sale.change_amount', 0);

        $this->assertDatabaseHas('sales', [
            'payment_method' => 'QRIS',
            'payment_reference' => 'QRIS-123456',
            'total' => 19980,
            'paid_amount' => 19980,
            'change_amount' => 0,
        ]);
    }

    public function test_checkout_caps_discount_and_groups_duplicate_items(): void
    {
        CafeCatalog::ensure();

        $product = Product::query()->where('sku', 'ESP-001')->firstOrFail();

        $this
            ->actingAs(User::factory()->create())
            ->postJson('/sales', [
                'customer_name' => 'Dina',
                'cashier_name' => CafeCatalog::store()['cashier'],
                'order_type' => 'Take away',
                'payment_method' => 'Tunai',
                'discount' => 999999,
                'paid_amount' => 100000,
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 1],
                    ['product_id' => $product->id, 'quantity' => 1],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('sale.subtotal', 36000)
            ->assertJsonPath('sale.discount', 36000)
            ->assertJsonPath('sale.tax', 0)
            ->assertJsonPath('sale.total', 0)
            ->assertJsonPath('sale.items.0.quantity', 2);

        $this->assertSame(40, $product->fresh()->stock);
        $this->assertSame(1, Sale::query()->count());
    }

    public function test_waiter_can_park_table_order_in_database_without_decreasing_stock(): void
    {
        CafeCatalog::ensure();

        $product = Product::query()->where('sku', 'ESP-001')->firstOrFail();

        $response = $this
            ->actingAs(User::factory()->create())
            ->postJson(route('orders.park'), [
                'customer_name' => 'Meja Satu',
                'table_number' => '1',
                'cashier_name' => CafeCatalog::store()['cashier'],
                'order_type' => 'Dine in',
                'discount' => 0,
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 1],
                ],
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('order.tableNumber', '1')
            ->assertJsonPath('order.status', 'parked')
            ->assertJsonPath('order.total', 19980);

        $this->assertDatabaseHas('sales', [
            'table_number' => '1',
            'status' => 'parked',
            'subtotal' => 18000,
            'total' => 19980,
            'paid_amount' => 0,
        ]);

        $this->assertSame(42, $product->fresh()->stock);
    }

    public function test_checkout_converts_parked_table_order_to_paid_sale(): void
    {
        CafeCatalog::ensure();

        $product = Product::query()->where('sku', 'ESP-001')->firstOrFail();
        $user = User::factory()->create();

        $parked = $this
            ->actingAs($user)
            ->postJson(route('orders.park'), [
                'customer_name' => 'Dewi',
                'table_number' => '7',
                'cashier_name' => CafeCatalog::store()['cashier'],
                'order_type' => 'Dine in',
                'discount' => 0,
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 2],
                ],
            ])
            ->assertOk()
            ->json('order');

        $this->assertSame(42, $product->fresh()->stock);

        $this
            ->actingAs($user)
            ->postJson('/sales', [
                'order_id' => $parked['id'],
                'customer_name' => 'Dewi',
                'table_number' => '7',
                'cashier_name' => CafeCatalog::store()['cashier'],
                'order_type' => 'Dine in',
                'payment_method' => 'QRIS',
                'payment_reference' => 'QRIS-MEJA-7',
                'discount' => 0,
                'paid_amount' => 39960,
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 2],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('sale.table_number', '7')
            ->assertJsonPath('sale.status', null)
            ->assertJsonPath('sale.payment_method', 'QRIS')
            ->assertJsonPath('sale.total', 39960);

        $this->assertDatabaseHas('sales', [
            'id' => $parked['id'],
            'table_number' => '7',
            'status' => 'paid',
            'payment_reference' => 'QRIS-MEJA-7',
            'total' => 39960,
        ]);

        $this->assertSame(40, $product->fresh()->stock);
        $this->assertSame(1, Sale::query()->count());
    }

    public function test_open_order_list_excludes_paid_sales(): void
    {
        CafeCatalog::ensure();

        $product = Product::query()->where('sku', 'ESP-001')->firstOrFail();
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->postJson(route('orders.park'), [
                'table_number' => '3',
                'cashier_name' => CafeCatalog::store()['cashier'],
                'order_type' => 'Dine in',
                'discount' => 0,
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 1],
                ],
            ])
            ->assertOk();

        $this
            ->actingAs($user)
            ->getJson(route('orders.open'))
            ->assertOk()
            ->assertJsonCount(1, 'orders')
            ->assertJsonPath('orders.0.tableNumber', '3');
    }

    public function test_guest_customer_can_order_from_table_qr_without_login(): void
    {
        CafeCatalog::ensure();

        $product = Product::query()->where('sku', 'ESP-001')->firstOrFail();

        $this
            ->get(route('customer.table.menu', ['tableNumber' => '5']))
            ->assertOk()
            ->assertSee('Meja 5')
            ->assertSee('Kirim ke Kasir');

        $this
            ->postJson(route('customer.table.orders', ['tableNumber' => '5']), [
                'customer_name' => 'Rina',
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 2],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('order.table_number', '5')
            ->assertJsonPath('order.total', 39960);

        $this->assertDatabaseHas('sales', [
            'customer_name' => 'Rina',
            'table_number' => '5',
            'cashier_name' => 'Customer QR',
            'status' => 'parked',
            'subtotal' => 36000,
            'total' => 39960,
            'paid_amount' => 0,
        ]);

        $this->assertSame(42, $product->fresh()->stock);
    }

    public function test_qr_table_page_lists_all_configured_tables(): void
    {
        config(['store.table_count' => 4]);

        $response = $this
            ->actingAs(User::factory()->cashier()->create())
            ->get(route('customer.qr.index'));

        $response
            ->assertOk()
            ->assertSee('Meja')
            ->assertSee(route('customer.table.menu', ['tableNumber' => 1]), false)
            ->assertSee(route('customer.table.menu', ['tableNumber' => 4]), false)
            ->assertDontSee(route('customer.table.menu', ['tableNumber' => 5]), false);
    }

    public function test_qr_table_print_page_is_staff_only(): void
    {
        $this
            ->get(route('customer.qr.index'))
            ->assertRedirect(route('login'));
    }
}
