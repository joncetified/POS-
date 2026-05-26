<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Support\CafeCatalog;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(): View
    {
        CafeCatalog::ensure();

        return view('reports.index', $this->reportData());
    }

    public function print(): View
    {
        CafeCatalog::ensure();

        return view('reports.print', $this->reportData());
    }

    public function excel(): Response
    {
        CafeCatalog::ensure();

        $data = $this->reportData();
        $filename = 'laporan-pos-' . now()->format('Ymd-His') . '.xls';

        return response($this->buildExcelHtml($data), 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    public function pdf(): Response
    {
        CafeCatalog::ensure();

        $data = $this->reportData();
        $filename = 'laporan-pos-' . now()->format('Ymd-His') . '.pdf';

        return response($this->buildPdf($data), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function reportData(): array
    {
        $todaySales = Sale::query()
            ->whereDate('paid_at', today())
            ->where('status', 'paid');

        $monthSales = Sale::query()
            ->whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->where('status', 'paid');

        $todayFinancials = [
            'grossSales' => (clone $todaySales)->sum('subtotal'),
            'discounts' => (clone $todaySales)->sum('discount'),
            'taxes' => (clone $todaySales)->sum('tax'),
            'netSales' => (clone $todaySales)->sum('total'),
            'cashTendered' => (clone $todaySales)->sum('paid_amount'),
            'changeGiven' => (clone $todaySales)->sum('change_amount'),
        ];

        return [
            'store' => CafeCatalog::store(),
            'generatedAt' => now(),
            'todayRevenue' => $todayFinancials['netSales'],
            'todayOrders' => (clone $todaySales)->count(),
            'monthRevenue' => (clone $monthSales)->sum('total'),
            'todayFinancials' => $todayFinancials,
            'paymentSummary' => (clone $todaySales)
                ->selectRaw('payment_method, COUNT(*) as orders_count, SUM(total) as total_sales, SUM(paid_amount) as tendered, SUM(change_amount) as change_given')
                ->groupBy('payment_method')
                ->orderBy('payment_method')
                ->get(),
            'todaySales' => (clone $todaySales)
                ->with('items')
                ->latest('paid_at')
                ->get(),
            'lowStockProducts' => Product::query()
                ->with('category')
                ->where('is_active', true)
                ->where('stock', '<=', 10)
                ->orderBy('stock')
                ->get(),
            'topItems' => SaleItem::query()
                ->whereHas('sale', fn ($query) => $query->where('status', 'paid'))
                ->selectRaw('product_name, sku, SUM(quantity) as sold_qty, SUM(line_total) as revenue')
                ->groupBy('product_name', 'sku')
                ->orderByDesc('sold_qty')
                ->limit(8)
                ->get(),
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function buildExcelHtml(array $data): string
    {
        $money = fn ($value): string => 'Rp ' . number_format((int) $value, 0, ',', '.');
        $html = '<html><head><meta charset="UTF-8"></head><body>';
        $html .= '<h1>Laporan POS Cafe</h1>';
        $html .= '<p>' . e($data['store']['name']) . ' - ' . e($data['generatedAt']->timezone('Asia/Jakarta')->format('d/m/Y H:i')) . '</p>';

        $html .= '<h2>Ringkasan Keuangan Hari Ini</h2><table border="1">';
        foreach ([
            'Penjualan kotor' => $data['todayFinancials']['grossSales'],
            'Diskon' => $data['todayFinancials']['discounts'],
            'PPN 11%' => $data['todayFinancials']['taxes'],
            'Penjualan bersih' => $data['todayFinancials']['netSales'],
            'Uang diterima' => $data['todayFinancials']['cashTendered'],
            'Kembalian' => $data['todayFinancials']['changeGiven'],
        ] as $label => $value) {
            $html .= '<tr><td>' . e($label) . '</td><td>' . e($money($value)) . '</td></tr>';
        }
        $html .= '</table>';

        $html .= '<h2>Metode Pembayaran</h2><table border="1"><tr><th>Metode</th><th>Order</th><th>Penjualan</th><th>Diterima</th><th>Kembali</th></tr>';
        foreach ($data['paymentSummary'] as $payment) {
            $html .= '<tr><td>' . e($payment->payment_method) . '</td><td>' . $payment->orders_count . '</td><td>' . e($money($payment->total_sales)) . '</td><td>' . e($money($payment->tendered)) . '</td><td>' . e($money($payment->change_given)) . '</td></tr>';
        }
        $html .= '</table>';

        $html .= '<h2>Transaksi Hari Ini</h2><table border="1"><tr><th>Invoice</th><th>Pelanggan</th><th>Meja</th><th>Metode</th><th>Subtotal</th><th>Diskon</th><th>PPN</th><th>Total</th><th>Bayar</th><th>Kembali</th><th>Waktu</th></tr>';
        foreach ($data['todaySales'] as $sale) {
            $html .= '<tr><td>' . e($sale->invoice_number) . '</td><td>' . e($sale->customer_name ?: 'Umum') . '</td><td>' . e($sale->table_number ?: '-') . '</td><td>' . e($sale->payment_method) . '</td><td>' . e($money($sale->subtotal)) . '</td><td>' . e($money($sale->discount)) . '</td><td>' . e($money($sale->tax)) . '</td><td>' . e($money($sale->total)) . '</td><td>' . e($money($sale->paid_amount)) . '</td><td>' . e($money($sale->change_amount)) . '</td><td>' . e($sale->paid_at?->timezone('Asia/Jakarta')->format('d/m/Y H:i')) . '</td></tr>';
        }
        $html .= '</table>';

        $html .= '<h2>Produk Terlaris</h2><table border="1"><tr><th>Produk</th><th>SKU</th><th>Terjual</th><th>Omzet</th></tr>';
        foreach ($data['topItems'] as $item) {
            $html .= '<tr><td>' . e($item->product_name) . '</td><td>' . e($item->sku) . '</td><td>' . $item->sold_qty . '</td><td>' . e($money($item->revenue)) . '</td></tr>';
        }
        $html .= '</table></body></html>';

        return $html;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function buildPdf(array $data): string
    {
        $money = fn ($value): string => 'Rp ' . number_format((int) $value, 0, ',', '.');
        $lines = [
            'LAPORAN POS CAFE',
            $data['store']['name'],
            'Dibuat: ' . $data['generatedAt']->timezone('Asia/Jakarta')->format('d/m/Y H:i'),
            '',
            'RINGKASAN HARI INI',
            'Penjualan kotor : ' . $money($data['todayFinancials']['grossSales']),
            'Diskon          : ' . $money($data['todayFinancials']['discounts']),
            'PPN 11%         : ' . $money($data['todayFinancials']['taxes']),
            'Penjualan bersih: ' . $money($data['todayFinancials']['netSales']),
            'Uang diterima   : ' . $money($data['todayFinancials']['cashTendered']),
            'Kembalian       : ' . $money($data['todayFinancials']['changeGiven']),
            '',
            'METODE PEMBAYARAN',
        ];

        foreach ($data['paymentSummary'] as $payment) {
            $lines[] = $payment->payment_method . ' | Order: ' . $payment->orders_count . ' | Penjualan: ' . $money($payment->total_sales);
        }

        $lines[] = '';
        $lines[] = 'TRANSAKSI HARI INI';
        foreach ($data['todaySales'] as $sale) {
            $table = $sale->table_number ? ' | Meja ' . $sale->table_number : '';
            $lines[] = $sale->invoice_number . ' | ' . ($sale->customer_name ?: 'Umum') . $table . ' | ' . $sale->payment_method . ' | Total ' . $money($sale->total);
        }

        $lines[] = '';
        $lines[] = 'PRODUK TERLARIS';
        foreach ($data['topItems'] as $item) {
            $lines[] = $item->product_name . ' | ' . $item->sold_qty . ' terjual | ' . $money($item->revenue);
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

        foreach ($pages as $pageIndex => $pageLines) {
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
