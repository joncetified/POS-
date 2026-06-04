<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\CompanySetting;
use App\Models\InventoryMovement;
use App\Models\OperationalExpense;
use App\Models\Product;
use App\Models\SalaryPayment;
use App\Models\Sale;
use App\Models\User;
use App\Support\CafeCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
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
            ->assertSee('QR Meja')
            ->assertSee('Estimasi profit hari ini')
            ->assertDontSee('Income Report');
    }

    public function test_dashboard_income_report_is_only_visible_to_super_admin_and_manager(): void
    {
        $this
            ->actingAs(User::factory()->superAdmin()->create())
            ->get(route('dashboard.index'))
            ->assertOk()
            ->assertSee('Income Report')
            ->assertSee('Today')
            ->assertSee('Last Month');

        $this
            ->actingAs(User::factory()->manager()->create())
            ->get(route('dashboard.index'))
            ->assertOk()
            ->assertSee('Income Report');

        $this
            ->actingAs(User::factory()->admin()->create())
            ->get(route('dashboard.index'))
            ->assertOk()
            ->assertDontSee('Income Report');
    }

    public function test_operations_page_records_expense_salary_and_inventory_movement(): void
    {
        CafeCatalog::ensure();

        $admin = User::factory()->admin()->create();
        $product = Product::query()->where('sku', 'ESP-001')->firstOrFail();

        $this
            ->actingAs($admin)
            ->get(route('operations.index'))
            ->assertOk()
            ->assertSee('Operasional / ERP');

        $this
            ->actingAs($admin)
            ->post(route('operations.expenses.store'), [
                'category' => 'Listrik',
                'description' => 'Token listrik cafe',
                'amount' => 250000,
                'spent_at' => now()->toDateString(),
                'vendor' => 'PLN',
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('operational_expenses', [
            'category' => 'Listrik',
            'amount' => 250000,
        ]);

        $this
            ->actingAs($admin)
            ->post(route('operations.employees.store'), [
                'name' => 'Ayu Barista',
                'position' => 'Barista',
                'phone' => '08123456789',
                'base_salary' => 3500000,
                'is_active' => 1,
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $employee = Employee::query()->where('name', 'Ayu Barista')->firstOrFail();

        $this
            ->actingAs($admin)
            ->post(route('operations.salaries.store'), [
                'employee_id' => $employee->id,
                'period' => now()->format('Y-m'),
                'amount' => 3500000,
                'paid_at' => now()->toDateString(),
                'note' => 'Gaji bulan ini',
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('salary_payments', [
            'employee_id' => $employee->id,
            'amount' => 3500000,
        ]);

        $this
            ->actingAs($admin)
            ->post(route('operations.inventory.store'), [
                'product_id' => $product->id,
                'type' => 'in',
                'quantity' => 8,
                'unit_cost' => 12000,
                'occurred_at' => now()->toDateString(),
                'note' => 'Restock biji kopi',
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertSame(50, $product->fresh()->stock);
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'type' => 'in',
            'quantity' => 8,
            'total_cost' => 96000,
            'stock_before' => 42,
            'stock_after' => 50,
        ]);
        $this->assertSame(250000, OperationalExpense::query()->sum('amount'));
        $this->assertSame(3500000, SalaryPayment::query()->sum('amount'));
        $this->assertSame(96000, InventoryMovement::query()->sum('total_cost'));
    }

    public function test_cashier_cannot_access_operations_page(): void
    {
        $cashier = User::factory()->cashier()->create();

        $this->actingAs($cashier)->get(route('operations.index'))->assertForbidden();
    }

    public function test_reports_show_period_filter_and_income_chart(): void
    {
        CafeCatalog::ensure();

        $this
            ->actingAs(User::factory()->manager()->create())
            ->get(route('reports.index', ['period' => 'this_month']))
            ->assertOk()
            ->assertSee('Periode Laporan')
            ->assertSee('This Month')
            ->assertSee('Last Month')
            ->assertSee('Diagram Income This Month')
            ->assertSee('Metode Pembayaran This Month');
    }

    public function test_product_management_accepts_unique_barcode(): void
    {
        CafeCatalog::ensure();

        $admin = User::factory()->admin()->create();
        $categoryId = Product::query()->firstOrFail()->category_id;

        $this
            ->actingAs($admin)
            ->post(route('products.store'), [
                'category_id' => $categoryId,
                'sku' => 'BAR-001',
                'barcode' => '8991234567890',
                'name' => 'Cold Brew Botol',
                'price' => 45000,
                'stock' => 12,
                'unit' => 'botol',
                'tag' => 'Ready',
                'color' => '#0f766e',
                'is_active' => 1,
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('products', [
            'sku' => 'BAR-001',
            'barcode' => '8991234567890',
        ]);

        $this
            ->actingAs($admin)
            ->get(route('products.index'))
            ->assertOk()
            ->assertSee('data-image-input', false)
            ->assertSee('data-image-preview', false)
            ->assertSee('form="update-', false)
            ->assertSee('Simpan Gambar')
            ->assertSee('data:image/svg+xml', false)
            ->assertSee('8991234567890');

        $this
            ->actingAs($admin)
            ->post(route('products.store'), [
                'category_id' => $categoryId,
                'sku' => 'BAR-002',
                'barcode' => '8991234567890',
                'name' => 'Latte Botol',
                'price' => 42000,
                'stock' => 8,
                'unit' => 'botol',
                'tag' => 'Ready',
                'color' => '#0f766e',
                'is_active' => 1,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('barcode');
    }

    public function test_product_update_saves_uploaded_image(): void
    {
        Storage::fake('public');
        CafeCatalog::ensure();

        $admin = User::factory()->admin()->create();
        $product = Product::query()->where('sku', 'ESP-001')->firstOrFail();
        $image = UploadedFile::fake()->image('espresso.jpg', 320, 320);

        $this
            ->actingAs($admin)
            ->put(route('products.update', $product), [
                'category_id' => $product->category_id,
                'sku' => $product->sku,
                'barcode' => $product->barcode,
                'name' => $product->name,
                'price' => $product->price,
                'stock' => $product->stock,
                'unit' => $product->unit,
                'tag' => $product->tag,
                'package_contents' => $product->package_contents,
                'color' => $product->color,
                'image' => $image,
                'is_active' => 1,
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $product->refresh();

        $this->assertNotNull($product->image_path);
        Storage::disk('public')->assertExists($product->image_path);
    }

    public function test_pos_product_payload_includes_barcode_for_scan(): void
    {
        CafeCatalog::ensure();

        Product::query()
            ->where('sku', 'ESP-001')
            ->firstOrFail()
            ->update([
                'barcode' => '8991234567890',
                'package_contents' => 'Espresso + Air Mineral',
            ]);

        $this
            ->actingAs(User::factory()->create())
            ->get(route('pos.index'))
            ->assertOk()
            ->assertSee('Scan Produk')
            ->assertSee('Scan barcode / SKU')
            ->assertSee('"barcode":"8991234567890"', false)
            ->assertSee('"barcode_image_url":"data:image\/svg+xml', false)
            ->assertSee('"package_contents":"Espresso + Air Mineral"', false)
            ->assertSee('"image_url"', false);
    }

    public function test_simple_bundle_product_can_be_saved_without_components(): void
    {
        CafeCatalog::ensure();

        $admin = User::factory()->admin()->create();
        $categoryId = Product::query()->firstOrFail()->category_id;

        $this
            ->actingAs($admin)
            ->post(route('products.store'), [
                'category_id' => $categoryId,
                'sku' => 'PKT-001',
                'name' => 'Nasi + Ayam',
                'price' => 35000,
                'stock' => 10,
                'unit' => 'paket',
                'tag' => 'Promo',
                'package_contents' => 'Nasi + Ayam',
                'color' => '#ff965f',
                'is_bundle' => 1,
                'is_active' => 1,
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $bundle = Product::query()->where('sku', 'PKT-001')->firstOrFail();

        $this->assertTrue($bundle->is_bundle);
        $this->assertSame('Nasi + Ayam', $bundle->package_contents);
        $this->assertSame(10, $bundle->availableForSaleStock());
        $this->assertSame(0, $bundle->bundleItems()->count());
    }

    public function test_checkout_creates_sale_and_decreases_stock(): void
    {
        CafeCatalog::ensure();

        $product = Product::query()->where('sku', 'MLK-002')->firstOrFail();

        $response = $this
            ->actingAs(User::factory()->create())
            ->postJson('/sales', [
            'customer_name' => 'Budi',
            'customer_note' => 'Bawa gula terpisah',
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
            ->assertJsonPath('sale.customer_note', 'Bawa gula terpisah')
            ->assertJsonPath('sale.table_number', '1')
            ->assertJsonPath('sale.subtotal', 56000);

        $this->assertDatabaseHas('sales', [
            'customer_name' => 'Budi',
            'customer_note' => 'Bawa gula terpisah',
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

    public function test_checkout_simple_bundle_product_decreases_bundle_stock_only(): void
    {
        CafeCatalog::ensure();

        $categoryId = Product::query()->firstOrFail()->category_id;
        $bundle = Product::query()->create([
            'category_id' => $categoryId,
            'sku' => 'PKT-002',
            'name' => 'Nasi + Ayam',
            'price' => 35000,
            'stock' => 10,
            'unit' => 'paket',
            'tag' => 'Promo',
            'package_contents' => 'Nasi + Ayam',
            'color' => '#ff965f',
            'is_bundle' => true,
            'is_active' => true,
        ]);

        $this
            ->actingAs(User::factory()->create())
            ->postJson('/sales', [
                'customer_name' => 'Paket Customer',
                'cashier_name' => CafeCatalog::store()['cashier'],
                'order_type' => 'Dine in',
                'payment_method' => 'Tunai',
                'discount' => 0,
                'paid_amount' => 100000,
                'items' => [
                    ['product_id' => $bundle->id, 'quantity' => 2],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('sale.subtotal', 70000)
            ->assertJsonPath('sale.total', 77700);

        $this->assertSame(8, $bundle->fresh()->stock);
        $this->assertSame(0, $bundle->bundleItems()->count());
    }

    public function test_checkout_bundle_product_decreases_bundle_and_component_stock(): void
    {
        CafeCatalog::ensure();

        $espresso = Product::query()->where('sku', 'ESP-001')->firstOrFail();
        $milkCoffee = Product::query()->where('sku', 'MLK-002')->firstOrFail();
        $bundle = Product::query()->create([
            'category_id' => $espresso->category_id,
            'sku' => 'BND-001',
            'name' => 'Paket Promo Kopi',
            'price' => 40000,
            'stock' => 5,
            'unit' => 'paket',
            'tag' => 'Promo',
            'color' => '#ff965f',
            'is_bundle' => true,
            'is_active' => true,
        ]);
        $bundle->bundleItems()->create([
            'component_product_id' => $espresso->id,
            'quantity' => 2,
        ]);
        $bundle->bundleItems()->create([
            'component_product_id' => $milkCoffee->id,
            'quantity' => 1,
        ]);

        $this
            ->actingAs(User::factory()->create())
            ->postJson('/sales', [
                'customer_name' => 'Promo Customer',
                'cashier_name' => CafeCatalog::store()['cashier'],
                'order_type' => 'Dine in',
                'payment_method' => 'Tunai',
                'discount' => 0,
                'paid_amount' => 100000,
                'items' => [
                    ['product_id' => $bundle->id, 'quantity' => 2],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('sale.subtotal', 80000)
            ->assertJsonPath('sale.total', 88800);

        $this->assertSame(3, $bundle->fresh()->stock);
        $this->assertSame(38, $espresso->fresh()->stock);
        $this->assertSame(53, $milkCoffee->fresh()->stock);
        $this->assertDatabaseHas('sale_items', [
            'product_id' => $bundle->id,
            'product_name' => 'Paket Promo Kopi',
            'quantity' => 2,
            'line_total' => 80000,
        ]);
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

    public function test_checkout_accepts_barcode_payment(): void
    {
        CafeCatalog::ensure();

        CompanySetting::current(CafeCatalog::defaultStore())->update([
            'payment_barcode_path' => 'payment-barcodes/test.png',
        ]);

        $product = Product::query()->where('sku', 'ESP-001')->firstOrFail();
        $reference = 'BARCODE-' . now()->format('Ymd') . '-0001';

        $this
            ->actingAs(User::factory()->create())
            ->postJson('/sales', [
                'customer_name' => 'Budi Barcode',
                'cashier_name' => CafeCatalog::store()['cashier'],
                'order_type' => 'Dine in',
                'payment_method' => 'Barcode',
                'discount' => 0,
                'paid_amount' => 19980,
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 1],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('sale.payment_method', 'Barcode')
            ->assertJsonPath('sale.payment_reference', $reference)
            ->assertJsonPath('sale.total', 19980)
            ->assertJsonPath('sale.paid_amount', 19980)
            ->assertJsonPath('sale.change_amount', 0);

        $this->assertDatabaseHas('sales', [
            'payment_method' => 'Barcode',
            'payment_reference' => $reference,
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

    public function test_qris_charge_creates_midtrans_payment_without_decreasing_stock(): void
    {
        CafeCatalog::ensure();
        config([
            'services.midtrans.server_key' => 'test-server-key',
            'services.midtrans.is_production' => true,
        ]);

        Http::fake([
            'api.midtrans.com/v2/charge' => Http::response([
                'transaction_status' => 'pending',
                'actions' => [
                    [
                        'name' => 'generate-qr-code',
                        'url' => 'https://api.midtrans.com/v2/qris/test-qr',
                    ],
                ],
            ]),
        ]);

        $product = Product::query()->where('sku', 'ESP-001')->firstOrFail();

        $this
            ->actingAs(User::factory()->create())
            ->postJson(route('payments.qris.charge'), [
                'customer_name' => 'Nina',
                'cashier_name' => CafeCatalog::store()['cashier'],
                'order_type' => 'Dine in',
                'discount' => 0,
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 1],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('amount', 19980)
            ->assertJsonPath('qr_url', 'https://api.midtrans.com/v2/qris/test-qr');

        $this->assertSame(42, $product->fresh()->stock);
        $this->assertSame(0, Sale::query()->count());
    }

    public function test_qris_finalize_saves_sale_after_midtrans_settlement(): void
    {
        CafeCatalog::ensure();
        config([
            'services.midtrans.server_key' => 'test-server-key',
            'services.midtrans.is_production' => true,
        ]);

        Http::fake([
            'api.midtrans.com/v2/charge' => Http::response([
                'transaction_status' => 'pending',
                'actions' => [
                    [
                        'name' => 'generate-qr-code',
                        'url' => 'https://api.midtrans.com/v2/qris/test-qr',
                    ],
                ],
            ]),
            'api.midtrans.com/v2/*/status' => Http::response([
                'transaction_status' => 'settlement',
                'gross_amount' => '19980.00',
            ]),
        ]);

        $product = Product::query()->where('sku', 'ESP-001')->firstOrFail();
        $user = User::factory()->create();

        $charge = $this
            ->actingAs($user)
            ->postJson(route('payments.qris.charge'), [
                'customer_name' => 'Nina',
                'cashier_name' => CafeCatalog::store()['cashier'],
                'order_type' => 'Dine in',
                'discount' => 0,
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 1],
                ],
            ])
            ->assertOk()
            ->json();

        $this
            ->actingAs($user)
            ->postJson(route('payments.qris.finalize'), [
                'midtrans_order_id' => $charge['order_id'],
            ])
            ->assertOk()
            ->assertJsonPath('sale.payment_method', 'QRIS')
            ->assertJsonPath('sale.payment_reference', $charge['order_id'])
            ->assertJsonPath('sale.total', 19980);

        $this->assertDatabaseHas('sales', [
            'payment_method' => 'QRIS',
            'payment_reference' => $charge['order_id'],
            'total' => 19980,
            'paid_amount' => 19980,
            'status' => 'paid',
        ]);

        $this->assertSame(41, $product->fresh()->stock);
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
            ->assertSee('Dengar Catatan')
            ->assertSee('Kirim ke Kasir');

        $this
            ->postJson(route('customer.table.orders', ['tableNumber' => '5']), [
                'customer_name' => 'Rina',
                'customer_note' => 'Less sugar, tanpa es',
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 2],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('order.table_number', '5')
            ->assertJsonPath('order.customer_note', 'Less sugar, tanpa es')
            ->assertJsonPath('order.total', 39960);

        $this->assertDatabaseHas('sales', [
            'customer_name' => 'Rina',
            'customer_note' => 'Less sugar, tanpa es',
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
