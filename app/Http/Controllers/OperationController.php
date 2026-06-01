<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\InventoryMovement;
use App\Models\OperationalExpense;
use App\Models\Product;
use App\Models\SalaryPayment;
use App\Models\Sale;
use App\Support\CafeCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OperationController extends Controller
{
    public function index(): View
    {
        CafeCatalog::ensure();

        $monthSales = Sale::query()
            ->where('status', 'paid')
            ->whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year);

        $monthExpenses = OperationalExpense::query()
            ->whereMonth('spent_at', now()->month)
            ->whereYear('spent_at', now()->year);

        $monthSalaries = SalaryPayment::query()
            ->whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year);

        $monthInventoryCost = InventoryMovement::query()
            ->where('type', 'in')
            ->whereMonth('occurred_at', now()->month)
            ->whereYear('occurred_at', now()->year);

        $revenue = (clone $monthSales)->sum('total');
        $expenseTotal = (clone $monthExpenses)->sum('amount');
        $salaryTotal = (clone $monthSalaries)->sum('amount');
        $inventoryCost = (clone $monthInventoryCost)->sum('total_cost');

        return view('operations.index', [
            'store' => CafeCatalog::store(),
            'products' => Product::query()->with('category')->orderBy('name')->get(),
            'employees' => Employee::query()->orderByDesc('is_active')->orderBy('name')->get(),
            'salaryPayments' => SalaryPayment::query()->with('employee')->latest('paid_at')->limit(10)->get(),
            'expenses' => OperationalExpense::query()->latest('spent_at')->limit(10)->get(),
            'movements' => InventoryMovement::query()->with('product')->latest('occurred_at')->latest('id')->limit(12)->get(),
            'summary' => [
                'revenue' => $revenue,
                'expenses' => $expenseTotal,
                'salaries' => $salaryTotal,
                'inventory_cost' => $inventoryCost,
                'net' => $revenue - $expenseTotal - $salaryTotal - $inventoryCost,
            ],
        ]);
    }

    public function storeEmployee(Request $request): RedirectResponse
    {
        Employee::query()->create($request->validate([
            'name' => ['required', 'string', 'max:120'],
            'position' => ['required', 'string', 'max:80'],
            'phone' => ['nullable', 'string', 'max:40'],
            'base_salary' => ['required', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]) + ['is_active' => false]);

        return back()->with('status', 'Karyawan berhasil ditambahkan.');
    }

    public function storeSalary(Request $request): RedirectResponse
    {
        SalaryPayment::query()->create($request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'period' => ['required', 'date_format:Y-m'],
            'amount' => ['required', 'integer', 'min:0'],
            'paid_at' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:160'],
        ]));

        return back()->with('status', 'Pembayaran gaji berhasil dicatat.');
    }

    public function storeExpense(Request $request): RedirectResponse
    {
        OperationalExpense::query()->create($request->validate([
            'category' => ['required', 'string', 'max:80'],
            'description' => ['required', 'string', 'max:160'],
            'amount' => ['required', 'integer', 'min:0'],
            'spent_at' => ['required', 'date'],
            'vendor' => ['nullable', 'string', 'max:120'],
        ]));

        return back()->with('status', 'Biaya operasional berhasil dicatat.');
    }

    public function storeInventoryMovement(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'type' => ['required', Rule::in(['in', 'out', 'adjustment'])],
            'quantity' => ['required', 'integer', 'min:1'],
            'unit_cost' => ['nullable', 'integer', 'min:0'],
            'occurred_at' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:160'],
        ]);

        DB::transaction(function () use ($validated) {
            $product = Product::query()->lockForUpdate()->findOrFail($validated['product_id']);
            $stockBefore = $product->stock;
            $quantity = (int) $validated['quantity'];
            $stockAfter = match ($validated['type']) {
                'in' => $stockBefore + $quantity,
                'out' => max($stockBefore - $quantity, 0),
                'adjustment' => $quantity,
            };
            $unitCost = (int) ($validated['unit_cost'] ?? 0);

            $product->update(['stock' => $stockAfter]);

            InventoryMovement::query()->create([
                'product_id' => $product->id,
                'type' => $validated['type'],
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'total_cost' => $validated['type'] === 'in' ? $unitCost * $quantity : 0,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'occurred_at' => $validated['occurred_at'],
                'note' => $validated['note'] ?? null,
            ]);
        });

        return back()->with('status', 'Pergerakan stok berhasil dicatat.');
    }
}
