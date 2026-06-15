<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use App\Support\CafeCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SaleController extends Controller
{
    private const TAX_RATE = 0.11;
    private const PAYMENT_METHODS = ['Tunai', 'QRIS', 'Barcode'];

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

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate($this->checkoutRules());

        $sale = $this->createPaidSale($validated);

        return response()->json([
            'message' => 'Transaksi berhasil disimpan.',
            'sale' => $this->serializePaidSale($sale),
        ], 201);
    }

    public function qrisCharge(Request $request): JsonResponse
    {
        $validated = $request->validate($this->qrisOrderRules());
        $lines = $this->buildOrderLines($validated['items'], false);
        $totals = $this->calculateTotals($lines, (int) ($validated['discount'] ?? 0));

        if ($totals['total'] <= 0) {
            throw ValidationException::withMessages([
                'total' => 'Total QRIS harus lebih dari Rp 0.',
            ]);
        }

        $midtransOrderId = $this->nextMidtransQrisOrderId();
        $response = $this->midtransRequest('post', '/v2/charge', [
            'payment_type' => 'qris',
            'transaction_details' => [
                'order_id' => $midtransOrderId,
                'gross_amount' => $totals['total'],
            ],
            'custom_expiry' => [
                'expiry_duration' => 15,
                'unit' => 'minute',
            ],
        ]);

        Cache::put($this->qrisCacheKey($midtransOrderId), [
            'validated' => $validated,
            'gross_amount' => $totals['total'],
        ], now()->addMinutes(20));

        return response()->json([
            'message' => 'QRIS berhasil dibuat.',
            'order_id' => $midtransOrderId,
            'amount' => $totals['total'],
            'transaction_status' => $response['transaction_status'] ?? 'pending',
            'qr_url' => $this->midtransQrUrl($response),
            'qr_string' => $response['qr_string'] ?? data_get($response, 'qris.qr_string'),
        ]);
    }

    public function qrisFinalize(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'midtrans_order_id' => ['required', 'string', 'max:80'],
        ]);

        $midtransOrderId = $validated['midtrans_order_id'];
        $existingSale = Sale::query()
            ->where('payment_reference', $midtransOrderId)
            ->where('status', 'paid')
            ->first();

        if ($existingSale) {
            return response()->json([
                'message' => 'Transaksi QRIS sudah tersimpan.',
                'sale' => $this->serializePaidSale($existingSale->load('items.product')),
            ]);
        }

        $cached = Cache::get($this->qrisCacheKey($midtransOrderId));

        if (!$cached) {
            throw ValidationException::withMessages([
                'midtrans_order_id' => 'Sesi QRIS sudah habis. Buat QRIS baru.',
            ]);
        }

        $status = $this->midtransRequest('get', '/v2/' . rawurlencode($midtransOrderId) . '/status');
        $transactionStatus = $status['transaction_status'] ?? null;
        $fraudStatus = $status['fraud_status'] ?? null;

        if (!$this->isMidtransPaid($transactionStatus, $fraudStatus)) {
            return response()->json([
                'message' => 'QRIS belum dibayar.',
                'transaction_status' => $transactionStatus,
                'fraud_status' => $fraudStatus,
            ], 202);
        }

        $grossAmount = (int) round((float) ($status['gross_amount'] ?? 0));
        if ($grossAmount !== (int) $cached['gross_amount']) {
            throw ValidationException::withMessages([
                'midtrans_order_id' => 'Nominal pembayaran QRIS tidak sesuai.',
            ]);
        }

        $saleData = $cached['validated'];
        $saleData['payment_method'] = 'QRIS';
        $saleData['payment_reference'] = $midtransOrderId;
        $saleData['paid_amount'] = (int) $cached['gross_amount'];

        $sale = $this->createPaidSale($saleData);
        Cache::forget($this->qrisCacheKey($midtransOrderId));

        return response()->json([
            'message' => 'Pembayaran QRIS berhasil.',
            'sale' => $this->serializePaidSale($sale),
        ]);
    }

    /**
     * @param array<string, mixed> $validated
     */
    private function createPaidSale(array $validated): Sale
    {
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

            $sale = new Sale();

            $sale->fill([
                'invoice_number' => $invoiceNumber,
                'customer_name' => $validated['customer_name'] ?? null,
                'customer_note' => $validated['customer_note'] ?? null,
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

            return $sale->load('items.product');
        });

        return $sale;
    }

    /**
     * @return array<string, mixed>
     */
    private function checkoutRules(): array
    {
        return [
            'customer_name' => ['nullable', 'string', 'max:120'],
            'customer_note' => ['nullable', 'string', 'max:255'],
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
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function qrisOrderRules(): array
    {
        return [
            'customer_name' => ['nullable', 'string', 'max:120'],
            'customer_note' => ['nullable', 'string', 'max:255'],
            'table_number' => ['nullable', 'string', 'max:20'],
            'cashier_name' => ['nullable', 'string', 'max:80'],
            'order_type' => ['required', 'string', 'max:40'],
            'discount' => ['nullable', 'integer', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function midtransRequest(string $method, string $path, array $payload = []): array
    {
        $serverKey = config('services.midtrans.server_key');

        if (!$serverKey) {
            throw ValidationException::withMessages([
                'midtrans' => 'Server Key Midtrans belum dikonfigurasi.',
            ]);
        }

        $baseUrl = rtrim(config('services.midtrans.is_production')
            ? config('services.midtrans.production_url')
            : config('services.midtrans.sandbox_url'), '/');

        try {
            $request = Http::withBasicAuth($serverKey, '')
                ->acceptJson()
                ->asJson()
                ->timeout(20);

            $response = $method === 'get'
                ? $request->get($baseUrl . $path)
                : $request->post($baseUrl . $path, $payload);
        } catch (\Throwable $exception) {
            throw ValidationException::withMessages([
                'midtrans' => 'Koneksi ke Midtrans gagal: ' . $exception->getMessage(),
            ]);
        }

        $data = $response->json() ?: [];

        if ($response->failed()) {
            $message = $data['status_message']
                ?? $data['message']
                ?? 'Request Midtrans gagal.';

            throw ValidationException::withMessages([
                'midtrans' => $message,
            ]);
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $response
     */
    private function midtransQrUrl(array $response): ?string
    {
        foreach ($response['actions'] ?? [] as $action) {
            $name = $action['name'] ?? '';

            if (in_array($name, ['generate-qr-code', 'generate-qris'], true)) {
                return $action['url'] ?? null;
            }
        }

        return $response['qr_url'] ?? null;
    }

    private function isMidtransPaid(?string $transactionStatus, ?string $fraudStatus): bool
    {
        if ($transactionStatus === 'settlement') {
            return true;
        }

        return $transactionStatus === 'capture'
            && in_array($fraudStatus, [null, 'accept'], true);
    }

    private function nextMidtransQrisOrderId(): string
    {
        return 'POS-QRIS-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(6));
    }

    private function qrisCacheKey(string $midtransOrderId): string
    {
        return 'midtrans:qris:' . $midtransOrderId;
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @return Collection<int, array{product: Product, quantity: int, line_total: int, stock_decrements: array<int, int>}>
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
            ->with('bundleItems.component')
            ->whereIn('id', $requestedItems->pluck('product_id'))
            ->where('is_active', true);

        $products = $query->get()->keyBy('id');
        $stockRequirements = [];

        $lines = $requestedItems->map(function (array $item) use ($products, &$stockRequirements) {
            $product = $products->get((int) $item['product_id']);
            $quantity = (int) $item['quantity'];

            if (!$product) {
                throw ValidationException::withMessages([
                    'items' => 'Produk tidak ditemukan atau sudah nonaktif.',
                ]);
            }

            $stockDecrements = [$product->id => $quantity];

            if ($product->is_bundle && $product->bundleItems->isNotEmpty()) {
                foreach ($product->bundleItems as $bundleItem) {
                    if (! $bundleItem->component) {
                        throw ValidationException::withMessages([
                            'items' => "Komponen paket {$product->name} tidak lengkap.",
                        ]);
                    }

                    $componentQuantity = $bundleItem->quantity * $quantity;
                    $stockDecrements[$bundleItem->component_product_id] = ($stockDecrements[$bundleItem->component_product_id] ?? 0) + $componentQuantity;
                }
            }

            foreach ($stockDecrements as $productId => $requiredQuantity) {
                $stockRequirements[$productId] = ($stockRequirements[$productId] ?? 0) + $requiredQuantity;
            }

            return [
                'product' => $product,
                'quantity' => $quantity,
                'line_total' => $product->price * $quantity,
                'stock_decrements' => $stockDecrements,
            ];
        });

        if ($lockStock && $stockRequirements !== []) {
            $stockProducts = Product::query()
                ->whereIn('id', array_keys($stockRequirements))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($stockRequirements as $productId => $requiredQuantity) {
                $stockProduct = $stockProducts->get($productId);

                if (! $stockProduct || $stockProduct->stock < $requiredQuantity) {
                    $name = $stockProduct?->name ?? 'Produk';
                    $stock = $stockProduct?->stock ?? 0;

                    throw ValidationException::withMessages([
                        'items' => "Stok {$name} tidak cukup. Butuh {$requiredQuantity}, sisa stok {$stock}.",
                    ]);
                }
            }
        }

        return $lines;
    }

    /**
     * @param Collection<int, array{product: Product, quantity: int, line_total: int, stock_decrements: array<int, int>}> $lines
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

    /**
     * @param Collection<int, array{product: Product, quantity: int, line_total: int, stock_decrements: array<int, int>}> $lines
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
                foreach ($line['stock_decrements'] as $productId => $stockQuantity) {
                    Product::query()->whereKey($productId)->decrement('stock', $stockQuantity);
                }
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
            'Barcode' => 'BARCODE',
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
    private function serializePaidSale(Sale $sale): array
    {
        return [
            'invoice_number' => $sale->invoice_number,
            'customer_name' => $sale->customer_name,
            'customer_note' => $sale->customer_note,
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
                'product_id' => $item->product_id,
                'sku' => $item->sku,
                'product_name' => $item->product_name,
                'image_url' => $item->product?->imageUrl(),
                'package_contents' => $item->product?->package_contents,
                'is_bundle' => (bool) ($item->product?->is_bundle ?? false),
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

        $html .= '<h2>Daftar Order Paid</h2><table border="1"><tr><th>Invoice</th><th>Pelanggan</th><th>Catatan</th><th>Meja</th><th>Item</th><th>Metode</th><th>Referensi</th><th>Subtotal</th><th>Diskon</th><th>PPN</th><th>Total</th><th>Bayar</th><th>Kembali</th><th>Waktu</th></tr>';
        foreach ($sales as $sale) {
            $items = $sale->items->map(fn ($item) => $item->product_name . ' x ' . $item->quantity)->implode(', ');
            $html .= '<tr><td>' . e($sale->invoice_number) . '</td><td>' . e($sale->customer_name ?: 'Umum') . '</td><td>' . e($sale->customer_note ?: '-') . '</td><td>' . e($sale->table_number ?: '-') . '</td><td>' . e($items) . '</td><td>' . e($sale->payment_method) . '</td><td>' . e($sale->payment_reference ?: '-') . '</td><td>' . e($money($sale->subtotal)) . '</td><td>' . e($money($sale->discount)) . '</td><td>' . e($money($sale->tax)) . '</td><td>' . e($money($sale->total)) . '</td><td>' . e($money($sale->paid_amount)) . '</td><td>' . e($money($sale->change_amount)) . '</td><td>' . e($sale->paid_at?->timezone('Asia/Jakarta')->format('d/m/Y H:i')) . '</td></tr>';
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
            if ($sale->customer_note) {
                $lines[] = '  Catatan: ' . $sale->customer_note;
            }
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
