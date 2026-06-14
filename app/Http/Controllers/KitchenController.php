<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Support\CafeCatalog;
use Illuminate\View\View;

class KitchenController extends Controller
{
    public function index(): View
    {
        $orders = Sale::query()
            ->with('items.product')
            ->whereIn('status', ['open', 'parked'])
            ->latest('updated_at')
            ->get();

        return view('kitchen.index', [
            'store' => CafeCatalog::store(),
            'orders' => $orders,
        ]);
    }
}
