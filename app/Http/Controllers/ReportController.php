<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Support\CafeCatalog;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(): View
    {
        CafeCatalog::ensure();

        $todaySales = Sale::query()
            ->whereDate('paid_at', today())
            ->where('status', 'paid');

        $monthSales = Sale::query()
            ->whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->where('status', 'paid');

        return view('reports.index', [
            'store' => CafeCatalog::store(),
            'todayRevenue' => (clone $todaySales)->sum('total'),
            'todayOrders' => (clone $todaySales)->count(),
            'monthRevenue' => (clone $monthSales)->sum('total'),
            'lowStockProducts' => Product::query()
                ->with('category')
                ->where('is_active', true)
                ->where('stock', '<=', 10)
                ->orderBy('stock')
                ->get(),
            'topItems' => SaleItem::query()
                ->selectRaw('product_name, sku, SUM(quantity) as sold_qty, SUM(line_total) as revenue')
                ->groupBy('product_name', 'sku')
                ->orderByDesc('sold_qty')
                ->limit(8)
                ->get(),
        ]);
    }
}
