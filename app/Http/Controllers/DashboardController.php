<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\InventoryMovement;
use App\Models\OperationalExpense;
use App\Models\Product;
use App\Models\SalaryPayment;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Support\CafeCatalog;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        if (Schema::hasTable('categories') && Schema::hasTable('products')) {
            CafeCatalog::ensure();
        }

        $hasSales = Schema::hasTable('sales');
        $hasSaleItems = Schema::hasTable('sale_items');
        $hasProducts = Schema::hasTable('products');

        $todaySales = $hasSales
            ? Sale::query()->whereDate('paid_at', today())->where('status', 'paid')
            : null;

        $openOrders = $hasSales
            ? Sale::query()
                ->with($hasSaleItems ? ['items'] : [])
                ->whereIn('status', ['open', 'parked'])
                ->latest('updated_at')
                ->get()
            : collect();

        $todayRevenue = $todaySales ? (clone $todaySales)->sum('total') : 0;
        $todayOrders = $todaySales ? (clone $todaySales)->count() : 0;
        $todayTax = $todaySales ? (clone $todaySales)->sum('tax') : 0;
        $todayDiscount = $todaySales ? (clone $todaySales)->sum('discount') : 0;
        $todayOperationalCost = Schema::hasTable('operational_expenses')
            ? OperationalExpense::query()->whereDate('spent_at', today())->sum('amount')
            : 0;
        $todaySalaryCost = Schema::hasTable('salary_payments')
            ? SalaryPayment::query()->whereDate('paid_at', today())->sum('amount')
            : 0;
        $todayInventoryCost = Schema::hasTable('inventory_movements')
            ? InventoryMovement::query()->where('type', 'in')->whereDate('occurred_at', today())->sum('total_cost')
            : 0;
        $canViewIncomeReport = in_array(request()->user()?->role, [UserRole::SuperAdmin, UserRole::Manager], true);

        return view('dashboard.index', [
            'store' => CafeCatalog::store(),
            'canViewIncomeReport' => $canViewIncomeReport,
            'incomeReport' => $canViewIncomeReport ? [
                'today' => $this->incomeBetween(now()->copy()->startOfDay(), now()->copy()->endOfDay()),
                'yesterday' => $this->incomeBetween(now()->copy()->subDay()->startOfDay(), now()->copy()->subDay()->endOfDay()),
                'thisMonth' => $this->incomeBetween(now()->copy()->startOfMonth(), now()->copy()->endOfMonth()),
                'lastMonth' => $this->incomeBetween(now()->copy()->subMonthNoOverflow()->startOfMonth(), now()->copy()->subMonthNoOverflow()->endOfMonth()),
            ] : [],
            'todayRevenue' => $todayRevenue,
            'todayOrders' => $todayOrders,
            'todayTax' => $todayTax,
            'todayDiscount' => $todayDiscount,
            'todayOperationalCost' => $todayOperationalCost,
            'todaySalaryCost' => $todaySalaryCost,
            'todayInventoryCost' => $todayInventoryCost,
            'todayEstimatedProfit' => $todayRevenue - $todayOperationalCost - $todaySalaryCost - $todayInventoryCost,
            'averageOrder' => $todayOrders > 0 ? (int) round($todayRevenue / $todayOrders) : 0,
            'openOrders' => $openOrders,
            'openOrdersTotal' => $openOrders->sum('total'),
            'activeProducts' => $hasProducts ? Product::query()->where('is_active', true)->count() : 0,
            'lowStockCount' => $hasProducts ? Product::query()->where('is_active', true)->where('stock', '<=', 10)->count() : 0,
            'paymentSummary' => $todaySales
                ? (clone $todaySales)
                    ->selectRaw('payment_method, COUNT(*) as orders_count, SUM(total) as total_sales')
                    ->groupBy('payment_method')
                    ->orderBy('payment_method')
                    ->get()
                : collect(),
            'latestSales' => $hasSales
                ? Sale::query()
                    ->with($hasSaleItems ? ['items'] : [])
                    ->where('status', 'paid')
                    ->latest('paid_at')
                    ->limit(6)
                    ->get()
                : collect(),
            'topItems' => $hasSaleItems
                ? SaleItem::query()
                    ->whereHas('sale', fn ($query) => $query->where('status', 'paid'))
                    ->selectRaw('product_name, sku, SUM(quantity) as sold_qty, SUM(line_total) as revenue')
                    ->groupBy('product_name', 'sku')
                    ->orderByDesc('sold_qty')
                    ->limit(6)
                    ->get()
                : collect(),
            'lowStockProducts' => $hasProducts
                ? Product::query()
                    ->with(Schema::hasTable('categories') ? ['category'] : [])
                    ->where('is_active', true)
                    ->where('stock', '<=', 10)
                    ->orderBy('stock')
                    ->limit(8)
                    ->get()
                : collect(),
        ]);
    }

    /**
     * @return array{orders: int, income: int}
     */
    private function incomeBetween($start, $end): array
    {
        if (! Schema::hasTable('sales')) {
            return ['orders' => 0, 'income' => 0];
        }

        $query = Sale::query()
            ->where('status', 'paid')
            ->whereBetween('paid_at', [$start, $end]);

        return [
            'orders' => (clone $query)->count(),
            'income' => (int) (clone $query)->sum('total'),
        ];
    }
}
