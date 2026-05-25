<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use App\Support\CafeCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SaleController extends Controller
{
    private const TAX_RATE = 0.11;

    public function index(): View
    {
        return view('sales.index', [
            'store' => CafeCatalog::store(),
            'sales' => Sale::query()
                ->with('items')
                ->latest('paid_at')
                ->paginate(20),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_name' => ['nullable', 'string', 'max:120'],
            'cashier_name' => ['nullable', 'string', 'max:80'],
            'order_type' => ['required', 'string', 'max:40'],
            'payment_method' => ['required', 'string', 'max:40'],
            'discount' => ['nullable', 'integer', 'min:0'],
            'paid_amount' => ['required', 'integer', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        $sale = DB::transaction(function () use ($validated) {
            $productIds = collect($validated['items'])->pluck('product_id')->unique()->values();
            $products = Product::query()
                ->whereIn('id', $productIds)
                ->where('is_active', true)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $lines = collect($validated['items'])->map(function (array $item) use ($products) {
                $product = $products->get((int) $item['product_id']);
                $quantity = (int) $item['quantity'];

                if (!$product) {
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
                    'line_total' => $product->price * $quantity,
                ];
            });

            $subtotal = $lines->sum('line_total');
            $discount = min((int) ($validated['discount'] ?? 0), $subtotal);
            $taxable = max($subtotal - $discount, 0);
            $tax = (int) round($taxable * self::TAX_RATE);
            $total = $taxable + $tax;
            $paidAmount = (int) $validated['paid_amount'];

            if ($paidAmount < $total) {
                throw ValidationException::withMessages([
                    'paid_amount' => 'Nominal bayar kurang dari total transaksi.',
                ]);
            }

            $sale = Sale::query()->create([
                'invoice_number' => $this->nextInvoiceNumber(),
                'customer_name' => $validated['customer_name'] ?? null,
                'cashier_name' => $validated['cashier_name'] ?? CafeCatalog::store()['cashier'],
                'order_type' => $validated['order_type'],
                'payment_method' => $validated['payment_method'],
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax' => $tax,
                'total' => $total,
                'paid_amount' => $paidAmount,
                'change_amount' => $paidAmount - $total,
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            $lines->each(function (array $line) use ($sale) {
                /** @var Product $product */
                $product = $line['product'];
                $quantity = $line['quantity'];

                $sale->items()->create([
                    'product_id' => $product->id,
                    'sku' => $product->sku,
                    'product_name' => $product->name,
                    'quantity' => $quantity,
                    'unit_price' => $product->price,
                    'line_total' => $line['line_total'],
                ]);

                $product->decrement('stock', $quantity);
            });

            return $sale->load('items');
        });

        return response()->json([
            'message' => 'Transaksi berhasil disimpan.',
            'sale' => [
                'invoice_number' => $sale->invoice_number,
                'customer_name' => $sale->customer_name,
                'cashier_name' => $sale->cashier_name,
                'order_type' => $sale->order_type,
                'payment_method' => $sale->payment_method,
                'subtotal' => $sale->subtotal,
                'discount' => $sale->discount,
                'tax' => $sale->tax,
                'total' => $sale->total,
                'paid_amount' => $sale->paid_amount,
                'change_amount' => $sale->change_amount,
                'paid_at' => $sale->paid_at?->timezone('Asia/Jakarta')->format('d/m/Y H:i'),
                'items' => $sale->items->map(fn ($item) => [
                    'sku' => $item->sku,
                    'product_name' => $item->product_name,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'line_total' => $item->line_total,
                ])->values(),
            ],
        ], 201);
    }

    private function nextInvoiceNumber(): string
    {
        $prefix = 'INV-' . now()->format('Ymd') . '-';
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
