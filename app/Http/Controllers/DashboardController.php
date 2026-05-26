<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Support\CafeCatalog;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        CafeCatalog::ensure();

        $todaySales = Sale::query()
            ->whereDate('paid_at', today())
            ->where('status', 'paid');

        $openOrders = Sale::query()
            ->with('items')
            ->whereIn('status', ['open', 'parked'])
            ->latest('updated_at')
            ->get();

        $todayRevenue = (clone $todaySales)->sum('total');
        $todayOrders = (clone $todaySales)->count();
        $todayTax = (clone $todaySales)->sum('tax');
        $todayDiscount = (clone $todaySales)->sum('discount');

        return view('dashboard.index', [
            'store' => CafeCatalog::store(),
            'todayRevenue' => $todayRevenue,
            'todayOrders' => $todayOrders,
            'todayTax' => $todayTax,
            'todayDiscount' => $todayDiscount,
            'averageOrder' => $todayOrders > 0 ? (int) round($todayRevenue / $todayOrders) : 0,
            'openOrders' => $openOrders,
            'openOrdersTotal' => $openOrders->sum('total'),
            'activeProducts' => Product::query()->where('is_active', true)->count(),
            'lowStockCount' => Product::query()->where('is_active', true)->where('stock', '<=', 10)->count(),
            'paymentSummary' => (clone $todaySales)
                ->selectRaw('payment_method, COUNT(*) as orders_count, SUM(total) as total_sales')
                ->groupBy('payment_method')
                ->orderBy('payment_method')
                ->get(),
            'latestSales' => Sale::query()
                ->with('items')
                ->where('status', 'paid')
                ->latest('paid_at')
                ->limit(6)
                ->get(),
            'topItems' => SaleItem::query()
                ->whereHas('sale', fn ($query) => $query->where('status', 'paid'))
                ->selectRaw('product_name, sku, SUM(quantity) as sold_qty, SUM(line_total) as revenue')
                ->groupBy('product_name', 'sku')
                ->orderByDesc('sold_qty')
                ->limit(6)
                ->get(),
            'lowStockProducts' => Product::query()
                ->with('category')
                ->where('is_active', true)
                ->where('stock', '<=', 10)
                ->orderBy('stock')
                ->limit(8)
                ->get(),
        ]);
    }
}
