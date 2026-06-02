<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Employee;
use App\Models\InventoryMovement;
use App\Models\OperationalExpense;
use App\Models\Product;
use App\Models\SalaryPayment;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class RealCafeDemoSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->clearOperationalData();

            $categories = $this->seedCategories();
            $products = $this->seedProducts($categories);
            $employees = $this->seedEmployees();
            $this->seedOperations($employees, $products);
            $this->seedSales($products);
        });
    }

    private function clearOperationalData(): void
    {
        Schema::disableForeignKeyConstraints();

        InventoryMovement::query()->delete();
        SalaryPayment::query()->delete();
        OperationalExpense::query()->delete();
        SaleItem::query()->delete();
        Sale::query()->delete();
        Product::query()->delete();
        Category::query()->delete();
        Employee::query()->delete();

        Schema::enableForeignKeyConstraints();
    }

    /**
     * @return array<string, Category>
     */
    private function seedCategories(): array
    {
        return collect([
            ['Espresso Bar', 1],
            ['Manual Brew', 2],
            ['Milk Coffee', 3],
            ['Non Coffee', 4],
            ['Pastry', 5],
        ])->mapWithKeys(function (array $category) {
            [$name, $sortOrder] = $category;

            return [$name => Category::query()->create([
                'name' => $name,
                'slug' => Str::slug($name),
                'sort_order' => $sortOrder,
            ])];
        })->all();
    }

    /**
     * @param array<string, Category> $categories
     * @return array<string, Product>
     */
    private function seedProducts(array $categories): array
    {
        $products = [
            ['Espresso Bar', 'ESP-001', 'Espresso Single', 18000, 40, 'cup', 'ES', '#8b451d'],
            ['Espresso Bar', 'ESP-002', 'Americano Hot', 24000, 36, 'cup', 'AH', '#334155'],
            ['Milk Coffee', 'MLK-001', 'Cappuccino', 32000, 28, 'cup', 'C', '#b45309'],
            ['Milk Coffee', 'MLK-002', 'Coffee Latte', 32000, 24, 'cup', 'CL', '#c08457'],
            ['Milk Coffee', 'MLK-003', 'Kopi Susu Gula Aren', 28000, 32, 'cup', 'GA', '#92400e'],
            ['Manual Brew', 'BRW-001', 'V60 Flores Bajawa', 38000, 18, 'cup', 'V60', '#0f766e'],
            ['Manual Brew', 'BRW-002', 'Japanese Iced Coffee', 40000, 16, 'cup', 'JIC', '#0369a1'],
            ['Non Coffee', 'NON-001', 'Matcha Latte', 34000, 22, 'cup', 'MT', '#4d7c0f'],
            ['Non Coffee', 'NON-002', 'Chocolate Signature', 32000, 20, 'cup', 'CS', '#78350f'],
            ['Pastry', 'PST-001', 'Butter Croissant', 26000, 14, 'pcs', 'BC', '#d97706'],
        ];

        return collect($products)->mapWithKeys(function (array $product) use ($categories) {
            [$category, $sku, $name, $price, $stock, $unit, $tag, $color] = $product;

            return [$sku => Product::query()->create([
                'category_id' => $categories[$category]->id,
                'sku' => $sku,
                'name' => $name,
                'price' => $price,
                'stock' => $stock,
                'unit' => $unit,
                'tag' => $tag,
                'color' => $color,
                'is_active' => true,
            ])];
        })->all();
    }

    /**
     * @return array<string, Employee>
     */
    private function seedEmployees(): array
    {
        return collect([
            ['Ayu Pratiwi', 'Head Barista', '0812-7711-2040', 4200000],
            ['Rizky Maulana', 'Kasir', '0813-4400-9812', 3600000],
            ['Maya Permatasari', 'Cook Helper', '0821-5508-1120', 3400000],
            ['Dimas Saputra', 'Floor Crew', '0812-9080-7731', 3200000],
        ])->mapWithKeys(function (array $employee) {
            [$name, $position, $phone, $baseSalary] = $employee;

            return [$name => Employee::query()->create([
                'name' => $name,
                'position' => $position,
                'phone' => $phone,
                'base_salary' => $baseSalary,
                'is_active' => true,
            ])];
        })->all();
    }

    /**
     * @param array<string, Employee> $employees
     * @param array<string, Product> $products
     */
    private function seedOperations(array $employees, array $products): void
    {
        foreach ($employees as $employee) {
            SalaryPayment::query()->create([
                'employee_id' => $employee->id,
                'period' => now()->format('Y-m'),
                'amount' => $employee->base_salary,
                'paid_at' => today(),
                'note' => 'Gaji bulanan cafe',
            ]);
        }

        foreach ([
            ['Bahan baku', 'Pembelian biji kopi Arabika 10kg', 1250000, 'Kopi Nusantara Supply'],
            ['Utilitas', 'Listrik dan air outlet', 760000, 'PLN / PDAM'],
            ['Sewa', 'Sewa ruko bulan berjalan', 5500000, 'Pemilik Ruko'],
            ['Perlengkapan', 'Cup, sedotan, dan paper bag', 680000, 'Kemasan Batam'],
        ] as [$category, $description, $amount, $vendor]) {
            OperationalExpense::query()->create([
                'category' => $category,
                'description' => $description,
                'amount' => $amount,
                'spent_at' => today(),
                'vendor' => $vendor,
            ]);
        }

        foreach ([
            ['ESP-001', 'in', 30, 6500, 'Restock espresso beans'],
            ['ESP-002', 'in', 24, 7000, 'Restock americano beans'],
            ['MLK-001', 'in', 20, 8500, 'Restock milk blend'],
            ['MLK-003', 'in', 22, 9000, 'Restock gula aren syrup'],
            ['PST-001', 'in', 18, 14000, 'Restock pastry pagi'],
            ['NON-001', 'out', 2, 0, 'Sample training barista'],
        ] as [$sku, $type, $quantity, $unitCost, $note]) {
            $product = $products[$sku];
            $stockAfter = $type === 'in' ? $product->stock + $quantity : $product->stock - $quantity;

            InventoryMovement::query()->create([
                'product_id' => $product->id,
                'type' => $type,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'total_cost' => $unitCost * $quantity,
                'stock_before' => $product->stock,
                'stock_after' => $stockAfter,
                'note' => $note,
                'occurred_at' => today(),
            ]);
        }
    }

    /**
     * @param array<string, Product> $products
     */
    private function seedSales(array $products): void
    {
        $sales = [
            ['Maya Permatasari', '5', 'Dine in', 'Tunai', null, [['MLK-001', 2], ['PST-001', 1]], 0, 160000, 'paid'],
            ['Yusuf Hidayat', '3', 'Dine in', 'QRIS', 'QRIS-260602-1001', [['ESP-002', 1], ['MLK-003', 1]], 0, 60000, 'paid'],
            ['Keisha Amanda', null, 'Take away', 'Tunai', null, [['NON-002', 1], ['PST-001', 1]], 5000, 70000, 'paid'],
            ['Budi Santoso', '7', 'Dine in', 'QRIS', 'QRIS-260602-1002', [['BRW-001', 1], ['NON-001', 1]], 0, 82000, 'paid'],
            ['Nadia Putri', '2', 'Dine in', 'Tunai', null, [['MLK-002', 2]], 0, 75000, 'paid'],
            ['Andika Pratama', null, 'Take away', 'QRIS', 'QRIS-260602-1003', [['ESP-001', 1], ['PST-001', 2]], 0, 80000, 'paid'],
            ['Sari Wulandari', '8', 'Dine in', 'Tunai', null, [['BRW-002', 1], ['NON-001', 1]], 3000, 90000, 'paid'],
            ['Rama Wijaya', '4', 'Dine in', 'QRIS', 'QRIS-260602-1004', [['MLK-003', 1], ['NON-002', 1]], 0, 70000, 'paid'],
            ['Anisa Cinta', '5', 'Dine in', 'Tunai', null, [['PST-001', 1], ['MLK-001', 1]], 0, 0, 'parked'],
            ['Lionel Hartono', '1', 'Dine in', 'Tunai', null, [['ESP-001', 1]], 0, 0, 'parked'],
        ];

        foreach ($sales as $index => [$customer, $table, $orderType, $payment, $reference, $items, $discount, $paidAmount, $status]) {
            $subtotal = collect($items)->sum(fn (array $item) => $products[$item[0]]->price * $item[1]);
            $tax = (int) round(max(0, $subtotal - $discount) * 0.11);
            $total = max(0, $subtotal - $discount) + $tax;
            $paidAt = $status === 'paid' ? Carbon::today()->setTime(9 + $index, 15 + ($index * 4) % 45) : null;
            $paid = $status === 'paid' ? max($paidAmount, $total) : 0;

            $sale = Sale::query()->create([
                'invoice_number' => sprintf('REAL-%s-%02d', now()->format('Ymd'), $index + 1),
                'customer_name' => $customer,
                'customer_note' => $index === 6 ? 'Less sugar, extra ice' : null,
                'table_number' => $table,
                'cashier_name' => 'Rizky Maulana',
                'order_type' => $orderType,
                'payment_method' => $payment,
                'payment_reference' => $reference,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax' => $tax,
                'total' => $total,
                'paid_amount' => $paid,
                'change_amount' => max(0, $paid - $total),
                'status' => $status,
                'paid_at' => $paidAt,
            ]);

            foreach ($items as [$sku, $quantity]) {
                $product = $products[$sku];

                $sale->items()->create([
                    'product_id' => $product->id,
                    'sku' => $product->sku,
                    'product_name' => $product->name,
                    'quantity' => $quantity,
                    'unit_price' => $product->price,
                    'line_total' => $product->price * $quantity,
                ]);
            }
        }
    }
}
