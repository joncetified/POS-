<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('demo:summary', function () {
    $this->line('categories: ' . \App\Models\Category::query()->count());
    $this->line('products: ' . \App\Models\Product::query()->count());
    $this->line('sales: ' . \App\Models\Sale::query()->count());
    $this->line('paid sales: ' . \App\Models\Sale::query()->where('status', 'paid')->count());
    $this->line('open bills: ' . \App\Models\Sale::query()->where('status', 'parked')->count());
    $this->line('sale items: ' . \App\Models\SaleItem::query()->count());
    $this->line('employees: ' . \App\Models\Employee::query()->count());
    $this->line('expenses: ' . \App\Models\OperationalExpense::query()->count());
    $this->line('inventory: ' . \App\Models\InventoryMovement::query()->count());
    $this->line('salaries: ' . \App\Models\SalaryPayment::query()->count());
    $this->line('today revenue: ' . \App\Models\Sale::query()
        ->where('status', 'paid')
        ->whereDate('paid_at', today())
        ->sum('total'));
})->purpose('Show local realistic cafe demo data counts');
