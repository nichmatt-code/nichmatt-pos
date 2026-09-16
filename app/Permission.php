<?php

namespace App;

enum Permission: string
{
    case Dashboard = 'dashboard';
    case Products = 'products';
    case Categories = 'categories';
    case Inventory = 'inventory';
    case StockOpname = 'stock-opname';
    case Reports = 'reports';
    case Preparation = 'preparation';
    case Employees = 'employees';
    case StoreSettings = 'store-settings';
    case Customers = 'customers';

    public function label(): string
    {
        return match ($this) {
            self::Dashboard => 'Dashboard',
            self::Products => 'Produk',
            self::Categories => 'Kategori',
            self::Inventory => 'Inventory / Gudang',
            self::StockOpname => 'Stock Opname',
            self::Reports => 'Laporan Penjualan',
            self::Preparation => 'Persiapan Pesanan',
            self::Employees => 'Kelola Karyawan',
            self::StoreSettings => 'Pengaturan Cabang',
            self::Customers => 'Data Pelanggan',
        };
    }
}
