<?php

namespace App\Http\Controllers;

use App\Models\InventoryMovement;
use App\Models\OperationalExpense;
use App\Models\Product;
use App\Models\SalaryPayment;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Support\CafeCatalog;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        CafeCatalog::ensure();

        return view('reports.index', $this->reportData($request->query('period', 'today')));
    }

    public function print(Request $request): View
    {
        CafeCatalog::ensure();

        return view('reports.print', $this->reportData($request->query('period', 'today')));
    }

    public function excel(Request $request): Response
    {
        CafeCatalog::ensure();

        $data = $this->reportData($request->query('period', 'today'));
        $filename = 'laporan-pos-' . now()->format('Ymd-His') . '.xls';

        return response($this->buildExcelHtml($data), 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    public function pdf(Request $request): Response
    {
        CafeCatalog::ensure();

        $data = $this->reportData($request->query('period', 'today'));
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
    private function reportData(?string $period = null): array
    {
        $periodConfig = $this->periodConfig($period);
        $periodSales = Sale::query()
            ->with('items')
            ->whereBetween('paid_at', [$periodConfig['start'], $periodConfig['end']])
            ->where('status', 'paid')
            ->orderBy('paid_at')
            ->get();

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
            'operationalExpenses' => OperationalExpense::query()->whereDate('spent_at', today())->sum('amount'),
            'salaryPayments' => SalaryPayment::query()->whereDate('paid_at', today())->sum('amount'),
            'inventoryPurchases' => InventoryMovement::query()->where('type', 'in')->whereDate('occurred_at', today())->sum('total_cost'),
        ];
        $todayFinancials['estimatedProfit'] = $todayFinancials['netSales']
            - $todayFinancials['operationalExpenses']
            - $todayFinancials['salaryPayments']
            - $todayFinancials['inventoryPurchases'];
        $periodFinancials = $this->financialsForPeriod($periodConfig['start'], $periodConfig['end']);
        $chartSeries = $this->chartSeries($periodSales, $periodConfig);
        $paymentChart = $periodSales
            ->groupBy('payment_method')
            ->map(fn ($sales, $method) => [
                'label' => $method ?: 'Tidak diketahui',
                'orders' => $sales->count(),
                'total' => (int) $sales->sum('total'),
            ])
            ->values();

        return [
            'store' => CafeCatalog::store(),
            'generatedAt' => now(),
            'periodOptions' => $this->periodOptions(),
            'selectedPeriod' => $periodConfig,
            'periodFinancials' => $periodFinancials,
            'periodOrders' => $periodSales->count(),
            'chartSeries' => $chartSeries,
            'chartMax' => max(1, (int) $chartSeries->max('total')),
            'paymentChart' => $paymentChart,
            'paymentChartTotal' => max(1, (int) $paymentChart->sum('total')),
            'todayRevenue' => $todayFinancials['netSales'],
            'todayOrders' => (clone $todaySales)->count(),
            'monthRevenue' => (clone $monthSales)->sum('total'),
            'todayFinancials' => $todayFinancials,
            'paymentSummary' => $periodSales
                ->groupBy('payment_method')
                ->map(fn ($sales, $method) => (object) [
                    'payment_method' => $method ?: 'Tidak diketahui',
                    'orders_count' => $sales->count(),
                    'total_sales' => (int) $sales->sum('total'),
                    'tendered' => (int) $sales->sum('paid_amount'),
                    'change_given' => (int) $sales->sum('change_amount'),
                ])
                ->sortBy('payment_method')
                ->values(),
            'periodSales' => $periodSales->sortByDesc('paid_at')->values(),
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
                ->whereHas('sale', fn ($query) => $query
                    ->where('status', 'paid')
                    ->whereBetween('paid_at', [$periodConfig['start'], $periodConfig['end']]))
                ->selectRaw('product_name, sku, SUM(quantity) as sold_qty, SUM(line_total) as revenue')
                ->groupBy('product_name', 'sku')
                ->orderByDesc('sold_qty')
                ->limit(8)
                ->get(),
            'recentOperationalExpenses' => OperationalExpense::query()->latest('spent_at')->limit(8)->get(),
            'recentSalaryPayments' => SalaryPayment::query()->with('employee')->latest('paid_at')->limit(8)->get(),
            'recentInventoryMovements' => InventoryMovement::query()->with('product')->latest('occurred_at')->latest('id')->limit(8)->get(),
        ];
    }

    /**
     * @return array<string, array{key: string, label: string}>
     */
    private function periodOptions(): array
    {
        return [
            'today' => ['key' => 'today', 'label' => 'Today'],
            'yesterday' => ['key' => 'yesterday', 'label' => 'Yesterday'],
            'this_week' => ['key' => 'this_week', 'label' => 'This Week'],
            'last_week' => ['key' => 'last_week', 'label' => 'Last Week'],
            'this_month' => ['key' => 'this_month', 'label' => 'This Month'],
            'last_month' => ['key' => 'last_month', 'label' => 'Last Month'],
            'this_year' => ['key' => 'this_year', 'label' => 'This Year'],
            'last_year' => ['key' => 'last_year', 'label' => 'Last Year'],
        ];
    }

    /**
     * @return array{key: string, label: string, start: \Illuminate\Support\Carbon, end: \Illuminate\Support\Carbon, granularity: string}
     */
    private function periodConfig(?string $period): array
    {
        $key = match ($period) {
            'daily' => 'today',
            'weekly' => 'this_week',
            'monthly' => 'this_month',
            'yearly' => 'this_year',
            'yesterday', 'this_week', 'last_week', 'this_month', 'last_month', 'this_year', 'last_year' => $period,
            default => 'today',
        };

        $now = now();

        return match ($key) {
            'yesterday' => [
                'key' => $key,
                'label' => 'Yesterday',
                'start' => $now->copy()->subDay()->startOfDay(),
                'end' => $now->copy()->subDay()->endOfDay(),
                'granularity' => 'hour',
            ],
            'this_week' => [
                'key' => $key,
                'label' => 'This Week',
                'start' => $now->copy()->startOfWeek(),
                'end' => $now->copy()->endOfWeek(),
                'granularity' => 'day',
            ],
            'last_week' => [
                'key' => $key,
                'label' => 'Last Week',
                'start' => $now->copy()->subWeek()->startOfWeek(),
                'end' => $now->copy()->subWeek()->endOfWeek(),
                'granularity' => 'day',
            ],
            'this_month' => [
                'key' => $key,
                'label' => 'This Month',
                'start' => $now->copy()->startOfMonth(),
                'end' => $now->copy()->endOfMonth(),
                'granularity' => 'day',
            ],
            'last_month' => [
                'key' => $key,
                'label' => 'Last Month',
                'start' => $now->copy()->subMonthNoOverflow()->startOfMonth(),
                'end' => $now->copy()->subMonthNoOverflow()->endOfMonth(),
                'granularity' => 'day',
            ],
            'this_year' => [
                'key' => $key,
                'label' => 'This Year',
                'start' => $now->copy()->startOfYear(),
                'end' => $now->copy()->endOfYear(),
                'granularity' => 'month',
            ],
            'last_year' => [
                'key' => $key,
                'label' => 'Last Year',
                'start' => $now->copy()->subYear()->startOfYear(),
                'end' => $now->copy()->subYear()->endOfYear(),
                'granularity' => 'month',
            ],
            default => [
                'key' => 'today',
                'label' => 'Today',
                'start' => $now->copy()->startOfDay(),
                'end' => $now->copy()->endOfDay(),
                'granularity' => 'hour',
            ],
        };
    }

    /**
     * @return array<string, int>
     */
    private function financialsForPeriod($start, $end): array
    {
        $sales = Sale::query()
            ->whereBetween('paid_at', [$start, $end])
            ->where('status', 'paid');

        $financials = [
            'grossSales' => (clone $sales)->sum('subtotal'),
            'discounts' => (clone $sales)->sum('discount'),
            'taxes' => (clone $sales)->sum('tax'),
            'netSales' => (clone $sales)->sum('total'),
            'cashTendered' => (clone $sales)->sum('paid_amount'),
            'changeGiven' => (clone $sales)->sum('change_amount'),
            'operationalExpenses' => OperationalExpense::query()->whereBetween('spent_at', [$start->toDateString(), $end->toDateString()])->sum('amount'),
            'salaryPayments' => SalaryPayment::query()->whereBetween('paid_at', [$start->toDateString(), $end->toDateString()])->sum('amount'),
            'inventoryPurchases' => InventoryMovement::query()->where('type', 'in')->whereBetween('occurred_at', [$start->toDateString(), $end->toDateString()])->sum('total_cost'),
        ];
        $financials['estimatedProfit'] = $financials['netSales']
            - $financials['operationalExpenses']
            - $financials['salaryPayments']
            - $financials['inventoryPurchases'];

        return $financials;
    }

    private function chartSeries($sales, array $periodConfig)
    {
        $series = collect();
        $cursor = $periodConfig['start']->copy();

        while ($cursor->lessThanOrEqualTo($periodConfig['end'])) {
            if ($periodConfig['granularity'] === 'month') {
                $key = $cursor->format('Y-m');
                $label = $cursor->format('M');
                $cursor->addMonth();
            } elseif ($periodConfig['granularity'] === 'hour') {
                $key = $cursor->format('Y-m-d H');
                $label = $cursor->format('H:00');
                $cursor->addHour();
            } else {
                $key = $cursor->format('Y-m-d');
                $label = $cursor->format('d M');
                $cursor->addDay();
            }

            $series->push(['key' => $key, 'label' => $label, 'orders' => 0, 'total' => 0]);
        }

        $grouped = $sales->groupBy(function (Sale $sale) use ($periodConfig): string {
            return match ($periodConfig['granularity']) {
                'month' => $sale->paid_at->format('Y-m'),
                'hour' => $sale->paid_at->format('Y-m-d H'),
                default => $sale->paid_at->format('Y-m-d'),
            };
        });

        return $series->map(function (array $point) use ($grouped): array {
            $sales = $grouped->get($point['key'], collect());

            return [
                'key' => $point['key'],
                'label' => $point['label'],
                'orders' => $sales->count(),
                'total' => (int) $sales->sum('total'),
            ];
        });
    }

    /**
     * @param array<string, mixed> $data
     */
    private function buildExcelHtml(array $data): string
    {
        $money = fn ($value): string => 'Rp ' . number_format((int) $value, 0, ',', '.');
        $html = '<html><head><meta charset="UTF-8"></head><body>';
        $html .= '<h1>Laporan POS Cafe</h1>';
        $html .= '<p>' . e($data['store']['name']) . ' - ' . e($data['selectedPeriod']['label']) . ' - ' . e($data['generatedAt']->timezone('Asia/Jakarta')->format('d/m/Y H:i')) . '</p>';

        $html .= '<h2>Ringkasan Keuangan ' . e($data['selectedPeriod']['label']) . '</h2><table border="1">';
        foreach ([
            'Penjualan kotor' => $data['periodFinancials']['grossSales'],
            'Diskon' => $data['periodFinancials']['discounts'],
            'PPN 11%' => $data['periodFinancials']['taxes'],
            'Penjualan bersih' => $data['periodFinancials']['netSales'],
            'Uang diterima' => $data['periodFinancials']['cashTendered'],
            'Kembalian' => $data['periodFinancials']['changeGiven'],
            'Biaya operasional' => $data['periodFinancials']['operationalExpenses'],
            'Gaji terbayar' => $data['periodFinancials']['salaryPayments'],
            'Belanja stok' => $data['periodFinancials']['inventoryPurchases'],
            'Estimasi profit' => $data['periodFinancials']['estimatedProfit'],
        ] as $label => $value) {
            $html .= '<tr><td>' . e($label) . '</td><td>' . e($money($value)) . '</td></tr>';
        }
        $html .= '</table>';

        $html .= '<h2>Metode Pembayaran</h2><table border="1"><tr><th>Metode</th><th>Order</th><th>Penjualan</th><th>Diterima</th><th>Kembali</th></tr>';
        foreach ($data['paymentSummary'] as $payment) {
            $html .= '<tr><td>' . e($payment->payment_method) . '</td><td>' . $payment->orders_count . '</td><td>' . e($money($payment->total_sales)) . '</td><td>' . e($money($payment->tendered)) . '</td><td>' . e($money($payment->change_given)) . '</td></tr>';
        }
        $html .= '</table>';

        $html .= '<h2>Transaksi ' . e($data['selectedPeriod']['label']) . '</h2><table border="1"><tr><th>Invoice</th><th>Pelanggan</th><th>Catatan</th><th>Meja</th><th>Metode</th><th>Subtotal</th><th>Diskon</th><th>PPN</th><th>Total</th><th>Bayar</th><th>Kembali</th><th>Waktu</th></tr>';
        foreach ($data['periodSales'] as $sale) {
            $html .= '<tr><td>' . e($sale->invoice_number) . '</td><td>' . e($sale->customer_name ?: 'Umum') . '</td><td>' . e($sale->customer_note ?: '-') . '</td><td>' . e($sale->table_number ?: '-') . '</td><td>' . e($sale->payment_method) . '</td><td>' . e($money($sale->subtotal)) . '</td><td>' . e($money($sale->discount)) . '</td><td>' . e($money($sale->tax)) . '</td><td>' . e($money($sale->total)) . '</td><td>' . e($money($sale->paid_amount)) . '</td><td>' . e($money($sale->change_amount)) . '</td><td>' . e($sale->paid_at?->timezone('Asia/Jakarta')->format('d/m/Y H:i')) . '</td></tr>';
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
            'Periode: ' . $data['selectedPeriod']['label'],
            'Dibuat: ' . $data['generatedAt']->timezone('Asia/Jakarta')->format('d/m/Y H:i'),
            '',
            'RINGKASAN ' . strtoupper($data['selectedPeriod']['label']),
            'Penjualan kotor : ' . $money($data['periodFinancials']['grossSales']),
            'Diskon          : ' . $money($data['periodFinancials']['discounts']),
            'PPN 11%         : ' . $money($data['periodFinancials']['taxes']),
            'Penjualan bersih: ' . $money($data['periodFinancials']['netSales']),
            'Uang diterima   : ' . $money($data['periodFinancials']['cashTendered']),
            'Kembalian       : ' . $money($data['periodFinancials']['changeGiven']),
            'Biaya operasional: ' . $money($data['periodFinancials']['operationalExpenses']),
            'Gaji terbayar   : ' . $money($data['periodFinancials']['salaryPayments']),
            'Belanja stok    : ' . $money($data['periodFinancials']['inventoryPurchases']),
            'Estimasi profit : ' . $money($data['periodFinancials']['estimatedProfit']),
            '',
            'METODE PEMBAYARAN',
        ];

        foreach ($data['paymentSummary'] as $payment) {
            $lines[] = $payment->payment_method . ' | Order: ' . $payment->orders_count . ' | Penjualan: ' . $money($payment->total_sales);
        }

        $lines[] = '';
        $lines[] = 'TRANSAKSI ' . strtoupper($data['selectedPeriod']['label']);
        foreach ($data['periodSales'] as $sale) {
            $table = $sale->table_number ? ' | Meja ' . $sale->table_number : '';
            $lines[] = $sale->invoice_number . ' | ' . ($sale->customer_name ?: 'Umum') . $table . ' | ' . $sale->payment_method . ' | Total ' . $money($sale->total);
            if ($sale->customer_note) {
                $lines[] = '  Catatan: ' . $sale->customer_note;
            }
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
