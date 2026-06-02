<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Employee;
use App\Models\InventoryMovement;
use App\Models\OperationalExpense;
use App\Models\Product;
use App\Models\SalaryPayment;
use App\Models\Sale;
use App\Models\SaleItem;
use Database\Seeders\RealCafeDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RealCafeDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_real_cafe_demo_seeder_creates_limited_realistic_operational_data(): void
    {
        $this->seed(RealCafeDemoSeeder::class);

        $this->assertSame(5, Category::query()->count());
        $this->assertSame(10, Product::query()->count());
        $this->assertSame(10, Sale::query()->count());
        $this->assertSame(8, Sale::query()->where('status', 'paid')->count());
        $this->assertSame(2, Sale::query()->where('status', 'parked')->count());
        $this->assertSame(18, SaleItem::query()->count());
        $this->assertSame(4, Employee::query()->count());
        $this->assertSame(4, OperationalExpense::query()->count());
        $this->assertSame(6, InventoryMovement::query()->count());
        $this->assertSame(4, SalaryPayment::query()->count());

        $this->assertDatabaseHas('products', [
            'sku' => 'ESP-001',
            'name' => 'Espresso Single',
            'price' => 18000,
        ]);

        $this->assertDatabaseHas('sales', [
            'invoice_number' => 'REAL-' . now()->format('Ymd') . '-01',
            'customer_name' => 'Maya Permatasari',
            'status' => 'paid',
        ]);
    }
}
