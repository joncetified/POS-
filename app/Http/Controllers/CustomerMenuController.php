<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Support\CafeCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CustomerMenuController extends Controller
{
    public function index(): View
    {
        return $this->menuView();
    }

    public function table(string $tableNumber): View
    {
        return $this->menuView($tableNumber);
    }

    public function qrIndex(): View
    {
        CafeCatalog::ensure();

        return view('customer.qr-tables', [
            'store' => CafeCatalog::store(),
            'tables' => CafeCatalog::tables(),
        ]);
    }

    public function submitTableOrder(Request $request, string $tableNumber): JsonResponse
    {
        $validated = $request->validate([
            'customer_name' => ['nullable', 'string', 'max:120'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        $sale = DB::transaction(function () use ($validated, $tableNumber) {
            $requestedItems = collect($validated['items'])
                ->groupBy('product_id')
                ->map(fn ($items, $productId) => [
                    'product_id' => (int) $productId,
                    'quantity' => (int) $items->sum('quantity'),
                ])
                ->values();

            $products = Product::query()
                ->whereIn('id', $requestedItems->pluck('product_id'))
                ->where('is_active', true)
                ->get()
                ->keyBy('id');

            $incomingLines = $requestedItems->map(function (array $item) use ($products) {
                $product = $products->get((int) $item['product_id']);
                $quantity = (int) $item['quantity'];

                if (! $product) {
                    throw ValidationException::withMessages([
                        'items' => 'Produk tidak ditemukan atau sudah nonaktif.',
                    ]);
                }

                if ($product->stock < $quantity) {
                    throw ValidationException::withMessages([
                        'items' => "Stok {$product->name} tidak cukup. Sisa stok {$product->stock}.",
                    ]);
                }

                return [
                    'product' => $product,
                    'quantity' => $quantity,
                ];
            });

            $sale = Sale::query()
                ->with('items')
                ->whereIn('status', ['open', 'parked'])
                ->where('table_number', $tableNumber)
                ->lockForUpdate()
                ->first();

            if (! $sale) {
                $sale = Sale::query()->create([
                    'invoice_number' => $this->nextOrderNumber(),
                    'customer_name' => $validated['customer_name'] ?? null,
                    'table_number' => $tableNumber,
                    'cashier_name' => 'Customer QR',
                    'order_type' => 'Dine in',
                    'payment_method' => 'Tunai',
                    'subtotal' => 0,
                    'discount' => 0,
                    'tax' => 0,
                    'total' => 0,
                    'paid_amount' => 0,
                    'change_amount' => 0,
                    'status' => 'parked',
                    'paid_at' => null,
                ]);
                $sale->load('items');
            } elseif (! empty($validated['customer_name'])) {
                $sale->customer_name = $validated['customer_name'];
            }

            foreach ($incomingLines as $line) {
                /** @var Product $product */
                $product = $line['product'];
                $existing = $sale->items->firstWhere('product_id', $product->id);
                $quantity = $line['quantity'] + ($existing?->quantity ?? 0);

                if ($product->stock < $quantity) {
                    throw ValidationException::withMessages([
                        'items' => "Total order {$product->name} melebihi stok. Sisa stok {$product->stock}.",
                    ]);
                }

                if ($existing) {
                    $existing->update([
                        'quantity' => $quantity,
                        'line_total' => $product->price * $quantity,
                    ]);
                } else {
                    $sale->items()->create([
                        'product_id' => $product->id,
                        'sku' => $product->sku,
                        'product_name' => $product->name,
                        'quantity' => $line['quantity'],
                        'unit_price' => $product->price,
                        'line_total' => $product->price * $line['quantity'],
                    ]);
                }
            }

            $sale->load('items');
            $subtotal = (int) $sale->items->sum('line_total');
            $tax = (int) round($subtotal * 0.11);
            $sale->fill([
                'subtotal' => $subtotal,
                'discount' => 0,
                'tax' => $tax,
                'total' => $subtotal + $tax,
                'status' => 'parked',
            ])->save();

            return $sale;
        });

        return response()->json([
            'message' => 'Pesanan meja masuk ke kasir.',
            'order' => [
                'id' => $sale->id,
                'table_number' => $sale->table_number,
                'total' => $sale->total,
            ],
        ], 201);
    }

    private function menuView(?string $tableNumber = null): View
    {
        CafeCatalog::ensure();

        return view('customer.menu', [
            'store' => CafeCatalog::store(),
            'tableNumber' => $tableNumber,
            'canOrder' => $tableNumber !== null,
            'categories' => Category::query()->orderBy('sort_order')->orderBy('name')->get(),
            'products' => Product::query()
                ->with('category')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    private function nextOrderNumber(): string
    {
        $prefix = 'ORD-' . now()->format('Ymd') . '-';
        $lastSale = Sale::query()
            ->where('invoice_number', 'like', $prefix . '%')
            ->latest('id')
            ->first();

        $next = $lastSale
            ? ((int) str_replace($prefix, '', $lastSale->invoice_number)) + 1
            : 1;

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
