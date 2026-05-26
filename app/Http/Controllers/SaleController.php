<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use App\Support\CafeCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SaleController extends Controller
{
    private const TAX_RATE = 0.11;
    private const PAYMENT_METHODS = ['Tunai', 'QRIS', 'Debit/Kredit', 'E-Wallet', 'Lainnya'];
    private const OPEN_STATUSES = ['open', 'parked'];

    public function index(): View
    {
        return view('sales.index', [
            'store' => CafeCatalog::store(),
            'sales' => $this->salesQuery()->paginate(20),
        ]);
    }

    public function print(): View
    {
        return view('sales.print', [
            'store' => CafeCatalog::store(),
            'generatedAt' => now(),
            'sales' => $this->salesQuery()->get(),
            'totals' => $this->salesTotals(),
        ]);
    }

    public function excel(): Response
    {
        $filename = 'order-pos-' . now()->format('Ymd-His') . '.xls';

        return response($this->buildExcelHtml(), 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    public function pdf(): Response
    {
        $filename = 'order-pos-' . now()->format('Ymd-His') . '.pdf';

        return response($this->buildPdf(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    public function openOrders(): JsonResponse
    {
        $orders = Sale::query()
            ->with('items')
            ->whereIn('status', self::OPEN_STATUSES)
            ->latest('updated_at')
            ->get()
            ->map(fn (Sale $sale) => $this->serializeOpenOrder($sale))
            ->values();

        return response()->json(['orders' => $orders]);
    }

    public function park(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => ['nullable', 'integer', 'exists:sales,id'],
            'customer_name' => ['nullable', 'string', 'max:120'],
            'table_number' => ['required', 'string', 'max:20'],
            'cashier_name' => ['nullable', 'string', 'max:80'],
            'order_type' => ['required', 'string', 'max:40'],
            'discount' => ['nullable', 'integer', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        $sale = DB::transaction(function () use ($validated) {
            $lines = $this->buildOrderLines($validated['items'], false);
            $totals = $this->calculateTotals($lines, (int) ($validated['discount'] ?? 0));

            $sale = $this->openSaleForUpdate($validated['order_id'] ?? null, $validated['table_number']);

            if (!$sale) {
                $sale = new Sale([
                    'invoice_number' => $this->nextDocumentNumber('ORD'),
                    'status' => 'parked',
                    'payment_method' => 'Tunai',
                    'paid_amount' => 0,
                    'change_amount' => 0,
                ]);
            }

            $sale->fill([
                'customer_name' => $validated['customer_name'] ?? null,
                'table_number' => $validated['table_number'],
                'cashier_name' => $validated['cashier_name'] ?? CafeCatalog::store()['cashier'],
                'order_type' => $validated['order_type'],
                'payment_reference' => null,
                'subtotal' => $totals['subtotal'],
                'discount' => $totals['discount'],
                'tax' => $totals['tax'],
                'total' => $totals['total'],
                'status' => 'parked',
                'paid_at' => null,
            ])->save();

            $this->replaceSaleItems($sale, $lines, false);

            return $sale->load('items');
        });

        return response()->json([
            'message' => 'Order meja tersimpan di database.',
            'order' => $this->serializeOpenOrder($sale),
        ]);
    }

    public function destroyOpen(Sale $sale): JsonResponse
    {
        if (!in_array($sale->status, self::OPEN_STATUSES, true)) {
            abort(404);
        }

        $sale->delete();

        return response()->json(['message' => 'Order meja dihapus.']);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => ['nullable', 'integer', 'exists:sales,id'],
            'customer_name' => ['nullable', 'string', 'max:120'],
            'table_number' => ['nullable', 'string', 'max:20'],
            'cashier_name' => ['nullable', 'string', 'max:80'],
            'order_type' => ['required', 'string', 'max:40'],
            'payment_method' => ['required', 'string', 'max:40', Rule::in(self::PAYMENT_METHODS)],
            'payment_reference' => ['nullable', 'string', 'max:80'],
            'discount' => ['nullable', 'integer', 'min:0'],
            'paid_amount' => ['required', 'integer', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        $sale = DB::transaction(function () use ($validated) {
            $lines = $this->buildOrderLines($validated['items'], true);
            $totals = $this->calculateTotals($lines, (int) ($validated['discount'] ?? 0));
            $paymentMethod = $validated['payment_method'];
            $isCashPayment = $paymentMethod === 'Tunai';
            $paidAmount = $isCashPayment ? (int) $validated['paid_amount'] : $totals['total'];

            if ($isCashPayment && $paidAmount < $totals['total']) {
                throw ValidationException::withMessages([
                    'paid_amount' => 'Nominal bayar kurang dari total transaksi.',
                ]);
            }

            $invoiceNumber = $this->nextInvoiceNumber();
            $paymentReference = $isCashPayment
                ? null
                : ($validated['payment_reference'] ?? $this->defaultPaymentReference($paymentMethod, $invoiceNumber));

            $sale = null;
            if (!empty($validated['order_id'])) {
                $sale = Sale::query()
                    ->whereKey($validated['order_id'])
                    ->whereIn('status', self::OPEN_STATUSES)
                    ->lockForUpdate()
                    ->first();

                if (!$sale) {
                    throw ValidationException::withMessages([
                        'order_id' => 'Order meja tidak ditemukan atau sudah dibayar.',
                    ]);
                }
            }

            if (!$sale) {
                $sale = new Sale();
            }

            $sale->fill([
                'invoice_number' => $invoiceNumber,
                'customer_name' => $validated['customer_name'] ?? null,
                'table_number' => $validated['table_number'] ?? null,
                'cashier_name' => $validated['cashier_name'] ?? CafeCatalog::store()['cashier'],
                'order_type' => $validated['order_type'],
                'payment_method' => $paymentMethod,
                'payment_reference' => $paymentReference,
                'subtotal' => $totals['subtotal'],
                'discount' => $totals['discount'],
                'tax' => $totals['tax'],
                'total' => $totals['total'],
                'paid_amount' => $paidAmount,
                'change_amount' => $isCashPayment ? $paidAmount - $totals['total'] : 0,
                'status' => 'paid',
                'paid_at' => now(),
            ])->save();

            $this->replaceSaleItems($sale, $lines, true);

            return $sale->load('items');
        });

        return response()->json([
            'message' => 'Transaksi berhasil disimpan.',
            'sale' => $this->serializePaidSale($sale),
        ], 201);
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @return Collection<int, array{product: Product, quantity: int, line_total: int}>
     */
    private function buildOrderLines(array $items, bool $lockStock): Collection
    {
        $requestedItems = collect($items)
            ->groupBy('product_id')
            ->map(fn ($items, $productId) => [
                'product_id' => (int) $productId,
                'quantity' => (int) $items->sum('quantity'),
            ])
            ->values();

        $query = Product::query()
            ->whereIn('id', $requestedItems->pluck('product_id'))
            ->where('is_active', true);

        if ($lockStock) {
            $query->lockForUpdate();
        }

        $products = $query->get()->keyBy('id');

        return $requestedItems->map(function (array $item) use ($products, $lockStock) {
            $product = $products->get((int) $item['product_id']);
            $quantity = (int) $item['quantity'];

            if (!$product) {
                throw ValidationException::withMessages([
                    'items' => 'Produk tidak ditemukan atau sudah nonaktif.',
                ]);
            }

            if ($lockStock && $product->stock < $quantity) {
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
    }

    /**
     * @param Collection<int, array{product: Product, quantity: int, line_total: int}> $lines
     * @return array{subtotal: int, discount: int, tax: int, total: int}
     */
    private function calculateTotals(Collection $lines, int $requestedDiscount): array
    {
        $subtotal = (int) $lines->sum('line_total');
        $discount = min($requestedDiscount, $subtotal);
        $taxable = max($subtotal - $discount, 0);
        $tax = (int) round($taxable * self::TAX_RATE);

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'tax' => $tax,
            'total' => $taxable + $tax,
        ];
    }

    private function openSaleForUpdate(?int $orderId, string $tableNumber): ?Sale
    {
        if ($orderId) {
            $sale = Sale::query()
                ->whereKey($orderId)
                ->whereIn('status', self::OPEN_STATUSES)
                ->lockForUpdate()
                ->first();

            if (!$sale) {
                throw ValidationException::withMessages([
                    'order_id' => 'Order meja tidak ditemukan atau sudah dibayar.',
                ]);
            }

            return $sale;
        }

        return Sale::query()
            ->whereIn('status', self::OPEN_STATUSES)
            ->where('table_number', $tableNumber)
            ->lockForUpdate()
            ->first();
    }

    /**
     * @param Collection<int, array{product: Product, quantity: int, line_total: int}> $lines
     */
    private function replaceSaleItems(Sale $sale, Collection $lines, bool $decrementStock): void
    {
        $sale->items()->delete();

        $lines->each(function (array $line) use ($sale, $decrementStock) {
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

            if ($decrementStock) {
                $product->decrement('stock', $quantity);
            }
        });
    }

    private function nextInvoiceNumber(): string
    {
        return $this->nextDocumentNumber('INV');
    }

    private function nextDocumentNumber(string $prefixCode): string
    {
        $prefix = $prefixCode . '-' . now()->format('Ymd') . '-';
        $lastSale = Sale::query()
            ->where('invoice_number', 'like', $prefix . '%')
            ->latest('id')
            ->first();

        $next = $lastSale
            ? ((int) str_replace($prefix, '', $lastSale->invoice_number)) + 1
            : 1;

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    private function defaultPaymentReference(string $paymentMethod, string $invoiceNumber): string
    {
        $prefix = match ($paymentMethod) {
            'QRIS' => 'QRIS',
            'Debit/Kredit' => 'CARD',
            'E-Wallet' => 'EWALLET',
            default => 'PAY',
        };

        return $prefix . '-' . str_replace('INV-', '', $invoiceNumber);
    }

    private function salesQuery()
    {
        return Sale::query()
            ->with('items')
            ->where('status', 'paid')
            ->latest('paid_at')
            ->latest('id');
    }

    /**
     * @return array<string, int>
     */
    private function salesTotals(): array
    {
        $query = Sale::query()->where('status', 'paid');

        return [
            'orders' => (clone $query)->count(),
            'subtotal' => (clone $query)->sum('subtotal'),
            'discount' => (clone $query)->sum('discount'),
            'tax' => (clone $query)->sum('tax'),
            'total' => (clone $query)->sum('total'),
            'paid' => (clone $query)->sum('paid_amount'),
            'change' => (clone $query)->sum('change_amount'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeOpenOrder(Sale $sale): array
    {
        return [
            'id' => $sale->id,
            'label' => ($sale->table_number ? 'Meja ' . $sale->table_number : ($sale->customer_name ?: 'Order')) . ' - Rp ' . number_format($sale->total, 0, ',', '.'),
            'customer' => $sale->customer_name,
            'tableNumber' => $sale->table_number,
            'discount' => $sale->discount,
            'discountPercent' => 0,
            'discountMode' => 'amount',
            'payment' => 'Tunai',
            'paymentReference' => null,
            'total' => $sale->total,
            'status' => $sale->status,
            'items' => $sale->items->map(fn ($item) => [
                'product_id' => $item->product_id,
                'sku' => $item->sku,
                'qty' => $item->quantity,
                'product_name' => $item->product_name,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'line_total' => $item->line_total,
            ])->values(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializePaidSale(Sale $sale): array
    {
        return [
            'invoice_number' => $sale->invoice_number,
            'customer_name' => $sale->customer_name,
            'table_number' => $sale->table_number,
            'cashier_name' => $sale->cashier_name,
            'order_type' => $sale->order_type,
            'payment_method' => $sale->payment_method,
            'payment_reference' => $sale->payment_reference,
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
        ];
    }

    private function buildExcelHtml(): string
    {
        $money = fn ($value): string => 'Rp ' . number_format((int) $value, 0, ',', '.');
        $sales = $this->salesQuery()->get();
        $totals = $this->salesTotals();
        $html = '<html><head><meta charset="UTF-8"></head><body>';
        $html .= '<h1>Order / Transaksi POS Cafe</h1>';
        $html .= '<p>' . e(CafeCatalog::store()['name']) . ' - ' . e(now()->timezone('Asia/Jakarta')->format('d/m/Y H:i')) . '</p>';
        $html .= '<table border="1"><tr><th>Total Order</th><th>Subtotal</th><th>Diskon</th><th>PPN</th><th>Total</th><th>Bayar</th><th>Kembali</th></tr>';
        $html .= '<tr><td>' . $totals['orders'] . '</td><td>' . e($money($totals['subtotal'])) . '</td><td>' . e($money($totals['discount'])) . '</td><td>' . e($money($totals['tax'])) . '</td><td>' . e($money($totals['total'])) . '</td><td>' . e($money($totals['paid'])) . '</td><td>' . e($money($totals['change'])) . '</td></tr></table>';

        $html .= '<h2>Daftar Order Paid</h2><table border="1"><tr><th>Invoice</th><th>Pelanggan</th><th>Meja</th><th>Item</th><th>Metode</th><th>Referensi</th><th>Subtotal</th><th>Diskon</th><th>PPN</th><th>Total</th><th>Bayar</th><th>Kembali</th><th>Waktu</th></tr>';
        foreach ($sales as $sale) {
            $items = $sale->items->map(fn ($item) => $item->product_name . ' x ' . $item->quantity)->implode(', ');
            $html .= '<tr><td>' . e($sale->invoice_number) . '</td><td>' . e($sale->customer_name ?: 'Umum') . '</td><td>' . e($sale->table_number ?: '-') . '</td><td>' . e($items) . '</td><td>' . e($sale->payment_method) . '</td><td>' . e($sale->payment_reference ?: '-') . '</td><td>' . e($money($sale->subtotal)) . '</td><td>' . e($money($sale->discount)) . '</td><td>' . e($money($sale->tax)) . '</td><td>' . e($money($sale->total)) . '</td><td>' . e($money($sale->paid_amount)) . '</td><td>' . e($money($sale->change_amount)) . '</td><td>' . e($sale->paid_at?->timezone('Asia/Jakarta')->format('d/m/Y H:i')) . '</td></tr>';
        }
        $html .= '</table></body></html>';

        return $html;
    }

    private function buildPdf(): string
    {
        $money = fn ($value): string => 'Rp ' . number_format((int) $value, 0, ',', '.');
        $totals = $this->salesTotals();
        $lines = [
            'ORDER / TRANSAKSI POS CAFE',
            CafeCatalog::store()['name'],
            'Dibuat: ' . now()->timezone('Asia/Jakarta')->format('d/m/Y H:i'),
            '',
            'RINGKASAN PAID',
            'Total order : ' . $totals['orders'],
            'Subtotal    : ' . $money($totals['subtotal']),
            'Diskon      : ' . $money($totals['discount']),
            'PPN         : ' . $money($totals['tax']),
            'Total       : ' . $money($totals['total']),
            'Bayar       : ' . $money($totals['paid']),
            'Kembali     : ' . $money($totals['change']),
            '',
            'DAFTAR ORDER PAID',
        ];

        foreach ($this->salesQuery()->get() as $sale) {
            $table = $sale->table_number ? ' | Meja ' . $sale->table_number : '';
            $lines[] = $sale->invoice_number . ' | ' . ($sale->customer_name ?: 'Umum') . $table . ' | ' . $sale->payment_method . ' | Total ' . $money($sale->total);
            foreach ($sale->items as $item) {
                $lines[] = '  - ' . $item->product_name . ' x ' . $item->quantity . ' = ' . $money($item->line_total);
            }
        }

        return $this->simplePdf($lines);
    }

    /**
     * @param list<string> $lines
     */
    private function simplePdf(array $lines): string
    {
        $pages = array_chunk($lines, 42);
        $objects = [];
        $pagesKids = [];

        foreach ($pages as $pageLines) {
            $content = "BT\n/F1 10 Tf\n50 790 Td\n14 TL\n";
            foreach ($pageLines as $line) {
                $content .= '(' . $this->pdfText($line) . ") Tj\nT*\n";
            }
            $content .= "ET";

            $contentId = count($objects) + 1;
            $objects[$contentId] = '<< /Length ' . strlen($content) . " >>\nstream\n" . $content . "\nendstream";
            $pageId = count($objects) + 1;
            $objects[$pageId] = '<< /Type /Page /Parent 0 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 0 0 R >> >> /Contents ' . $contentId . ' 0 R >>';
            $pagesKids[] = $pageId;
        }

        $fontId = count($objects) + 1;
        $objects[$fontId] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';

        $pagesId = count($objects) + 1;
        foreach ($pagesKids as $pageId) {
            $objects[$pageId] = str_replace('/Parent 0 0 R', '/Parent ' . $pagesId . ' 0 R', $objects[$pageId]);
            $objects[$pageId] = str_replace('/F1 0 0 R', '/F1 ' . $fontId . ' 0 R', $objects[$pageId]);
        }
        $objects[$pagesId] = '<< /Type /Pages /Kids [' . implode(' ', array_map(fn ($id) => $id . ' 0 R', $pagesKids)) . '] /Count ' . count($pagesKids) . ' >>';

        $catalogId = count($objects) + 1;
        $objects[$catalogId] = '<< /Type /Catalog /Pages ' . $pagesId . ' 0 R >>';

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $id => $object) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $id . " 0 obj\n" . $object . "\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
        for ($id = 1; $id <= count($objects); $id++) {
            $pdf .= str_pad((string) $offsets[$id], 10, '0', STR_PAD_LEFT) . " 00000 n \n";
        }
        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . ' /Root ' . $catalogId . " 0 R >>\nstartxref\n" . $xrefOffset . "\n%%EOF";

        return $pdf;
    }

    private function pdfText(string $text): string
    {
        $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;

        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }
}
