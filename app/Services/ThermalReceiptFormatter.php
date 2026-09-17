<?php

namespace App\Services;

use App\Models\LossRecord;
use App\Models\Store;
use App\Models\Transaction;

/**
 * Renders receipts as plain, fixed-width text lines so they can be sent
 * as raw ESC/POS bytes to a Bluetooth thermal printer from the browser,
 * mirroring the content of the HTML receipt views.
 */
class ThermalReceiptFormatter
{
    /**
     * @return array<int, string>
     */
    public static function forTransaction(Transaction $transaction): array
    {
        $store = $transaction->store;
        $width = self::width($store);
        $lines = [];

        $lines = array_merge($lines, self::storeHeader($store, $width));
        $lines[] = self::divider($width);
        $lines[] = $transaction->transaction_no;
        $lines[] = $transaction->created_at->format('d/m/Y H:i').' - Kasir: '.$transaction->user->name;
        if ($transaction->customer_name) {
            $lines[] = 'Customer: '.$transaction->customer_name;
        }
        if ($transaction->note) {
            $lines[] = 'Catatan: '.$transaction->note;
        }
        $lines[] = self::divider($width);

        foreach ($transaction->items as $item) {
            $lines[] = $item->product_name;
            $lines[] = self::twoCol($item->qty.' x '.self::money($item->price), self::money($item->subtotal), $width);
            if ($item->note) {
                $lines[] = '- '.$item->note;
            }
        }

        $lines[] = self::divider($width);
        $lines[] = self::twoCol('Subtotal', self::money($transaction->subtotal), $width);
        if ($transaction->discount > 0) {
            $lines[] = self::twoCol('Diskon', '-'.self::money($transaction->discount), $width);
        }
        if ($transaction->coupon_discount_amount > 0) {
            $lines[] = self::twoCol('Diskon Kupon', '-'.self::money($transaction->coupon_discount_amount), $width);
        }
        if ($transaction->service_charge_amount > 0) {
            $lines[] = self::twoCol('Service Charge', self::money($transaction->service_charge_amount), $width);
        }
        if ($transaction->tax_amount > 0) {
            $lines[] = self::twoCol('Pajak', self::money($transaction->tax_amount), $width);
        }
        $lines[] = self::twoCol('Total', self::money($transaction->total), $width);
        $lines[] = self::twoCol('Bayar ('.strtoupper($transaction->payment_method).')', self::money($transaction->paid_amount), $width);
        $lines[] = self::twoCol('Kembali', self::money($transaction->change_amount), $width);
        $lines[] = self::divider($width);
        $lines[] = self::center($store->receiptFooterText(), $width);

        return $lines;
    }

    /**
     * @param  array<string, mixed>  $bill
     * @return array<int, string>
     */
    public static function forBill(Store $store, array $bill): array
    {
        $width = self::width($store);
        $lines = [];

        $lines = array_merge($lines, self::storeHeader($store, $width));
        $lines[] = self::center('BILL SEMENTARA - BELUM DIBAYAR', $width);
        $lines[] = self::divider($width);
        $lines[] = now()->format('d/m/Y H:i');
        if (! empty($bill['customer_name'])) {
            $lines[] = 'Customer: '.$bill['customer_name'];
        }
        if (! empty($bill['note'])) {
            $lines[] = 'Catatan: '.$bill['note'];
        }
        $lines[] = self::divider($width);

        foreach ($bill['items'] as $item) {
            $lines[] = $item['name'];
            $lines[] = self::twoCol($item['qty'].' x '.self::money($item['price']), self::money($item['price'] * $item['qty']), $width);
            if (! empty($item['note'])) {
                $lines[] = '- '.$item['note'];
            }
        }

        $lines[] = self::divider($width);
        $lines[] = self::twoCol('Subtotal', self::money($bill['subtotal']), $width);
        if ($bill['discount'] > 0) {
            $lines[] = self::twoCol('Diskon', '-'.self::money($bill['discount']), $width);
        }
        if (($bill['coupon_discount_amount'] ?? 0) > 0) {
            $lines[] = self::twoCol('Diskon Kupon', '-'.self::money($bill['coupon_discount_amount']), $width);
        }
        if (($bill['service_charge_amount'] ?? 0) > 0) {
            $lines[] = self::twoCol('Service Charge', self::money($bill['service_charge_amount']), $width);
        }
        if (($bill['tax_amount'] ?? 0) > 0) {
            $lines[] = self::twoCol('Pajak', self::money($bill['tax_amount']), $width);
        }
        $lines[] = self::twoCol('Total Tagihan', self::money($bill['total']), $width);
        $lines[] = self::divider($width);
        $lines[] = self::center('Mohon lakukan pembayaran ke kasir', $width);

        return $lines;
    }

    /**
     * @return array<int, string>
     */
    public static function forLossRecord(LossRecord $lossRecord): array
    {
        $store = $lossRecord->store;
        $width = self::width($store);
        $lines = [];

        $lines[] = self::center($store->name, $width);
        $lines[] = self::center('*** NOTA KERUGIAN ***', $width);
        $lines[] = self::divider($width);
        $lines[] = $lossRecord->loss_no;
        $lines[] = $lossRecord->created_at->format('d/m/Y H:i').' - Dicatat oleh: '.($lossRecord->user->name ?? '-');
        $lines[] = 'Alasan: '.$lossRecord->reason;
        $lines[] = self::divider($width);

        foreach ($lossRecord->items as $item) {
            $lines[] = $item->product_name;
            $lines[] = self::twoCol($item->qty.' x '.self::money($item->cost_price), self::money($item->subtotal_cost), $width);
        }

        $lines[] = self::divider($width);
        $lines[] = self::twoCol('Total Nilai Kerugian', self::money($lossRecord->total_cost_value), $width);
        $lines[] = self::divider($width);
        $lines[] = self::center('Dihitung dari harga modal, bukan penjualan', $width);

        return $lines;
    }

    /**
     * @return array<int, string>
     */
    private static function storeHeader(Store $store, int $width): array
    {
        $lines = [self::center($store->name, $width)];
        if ($store->address) {
            $lines[] = self::center($store->address, $width);
        }
        if ($store->phone) {
            $lines[] = self::center($store->phone, $width);
        }

        return $lines;
    }

    private static function width(Store $store): int
    {
        return $store->receipt_width === '58mm' ? 32 : 48;
    }

    private static function divider(int $width): string
    {
        return str_repeat('-', $width);
    }

    private static function center(string $text, int $width): string
    {
        $text = trim($text);
        if (strlen($text) >= $width) {
            return substr($text, 0, $width);
        }

        $padding = intdiv($width - strlen($text), 2);

        return str_repeat(' ', $padding).$text;
    }

    private static function twoCol(string $left, string $right, int $width): string
    {
        $right = substr($right, 0, $width);
        $available = max(0, $width - strlen($right));
        $left = substr($left, 0, $available);

        return $left.str_repeat(' ', $available - strlen($left)).$right;
    }

    private static function money(float|int $amount): string
    {
        return number_format($amount, 0, ',', '.');
    }
}
