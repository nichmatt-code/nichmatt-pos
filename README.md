# NichmattPOS

A multi-tenant point-of-sale (POS) application built for small shops (ruko). Each store's data is fully isolated, with owner/cashier roles, product & stock management, a cashier checkout screen, printable receipts, and sales reporting.

## Stack

- Laravel 13 (PHP 8.4)
- Livewire 3 / Volt (auth pages)
- MySQL
- Tailwind CSS

## Features

- **Multi-tenant** — one shared database, scoped per store via a global Eloquent scope
- **Roles** — `owner` (full access) and `kasir` (cashier, POS-only)
- **Products & categories** — CRUD with stock tracking and a stock movement ledger
- **POS terminal** — cart, discount, cash/QRIS/card payment, automatic stock deduction
- **Receipts** — printable, thermal-printer-friendly (80mm) receipt view
- **Sales reports** — omzet, transaction count, estimated profit, best sellers, date range filter

## Setup

```bash
composer install
npm install

cp .env.example .env
php artisan key:generate

# create the database named in .env (DB_DATABASE), then:
php artisan migrate --seed

npm run build
php artisan serve
```

## Demo accounts

Seeded by `php artisan db:seed` (password for all: `password`):

| Email | Role | Store |
|---|---|---|
| `owner@tokojaya.test` | owner | Toko Sembako Jaya |
| `kasir@tokojaya.test` | kasir | Toko Sembako Jaya |
| `owner@tokomakmur.test` | owner | Toko Elektronik Makmur |

## License

Proprietary — all rights reserved.
