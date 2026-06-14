<?php

namespace App\Support;

class PageAccess
{
    /**
     * @return array<string, array{label: string, description: string}>
     */
    public static function pages(): array
    {
        return [
            'page.dashboard' => [
                'label' => 'Dashboard',
                'description' => 'Ringkasan operasional, open bill, stok, dan pembayaran.',
            ],
            'page.pos' => [
                'label' => 'Kasir POS',
                'description' => 'Layar kasir, checkout, pembayaran, dan cetak struk.',
            ],
            'page.qr_tables' => [
                'label' => 'QR Meja',
                'description' => 'Halaman staff untuk print dan cek QR meja.',
            ],
            'page.customer_menu' => [
                'label' => 'Menu Pelanggan',
                'description' => 'Katalog menu untuk akun pelanggan.',
            ],
            'page.products' => [
                'label' => 'Produk & Stok',
                'description' => 'Tambah kategori, produk, harga, status aktif, dan stok.',
            ],
            'page.orders' => [
                'label' => 'Open Bill',
                'description' => 'Simpan, parkir, muat, dan hapus order meja.',
            ],
            'page.kitchen' => [
                'label' => 'Dapur',
                'description' => 'Daftar pesanan aktif untuk koki dengan text-to-voice.',
            ],
            'page.sales' => [
                'label' => 'Order / Transaksi',
                'description' => 'Melihat riwayat transaksi paid.',
            ],
            'page.sales_export' => [
                'label' => 'Export Order',
                'description' => 'Print, PDF, dan Excel data order/transaksi.',
            ],
            'page.reports' => [
                'label' => 'Laporan',
                'description' => 'Melihat ringkasan laporan penjualan dan stok.',
            ],
            'page.reports_export' => [
                'label' => 'Export Laporan',
                'description' => 'Print, PDF, dan Excel laporan.',
            ],
            'page.operations' => [
                'label' => 'Operasional / ERP',
                'description' => 'Biaya operasional, gaji karyawan, dan pergerakan stok.',
            ],
            'page.settings' => [
                'label' => 'Settings Website',
                'description' => 'Nama perusahaan, logo, alamat, manager, dan kontak.',
            ],
            'page.access_control' => [
                'label' => 'Akses User',
                'description' => 'Mengatur akses halaman berdasarkan role akun.',
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function permissions(): array
    {
        return array_keys(self::pages());
    }
}
