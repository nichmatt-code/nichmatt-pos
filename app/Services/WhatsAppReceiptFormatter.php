<?php

namespace App\Services;

use App\Models\Transaction;

/**
 * Builds a `https://wa.me/...` link that opens WhatsApp with the receipt
 * pre-filled as the message text - there's no API to attach a file/image
 * through a plain wa.me link, so the receipt itself is formatted as a
 * neat, itemized WhatsApp message instead (reusing the same aligned
 * layout ThermalReceiptFormatter renders for a real printer, wrapped in a
 * ``` monospace block so WhatsApp keeps the columns lined up).
 */
class WhatsAppReceiptFormatter
{
    /**
     * `null` when the transaction has no phone number to send to - callers
     * use this to decide whether to show the "Kirim via WhatsApp" button.
     */
    public static function waLink(Transaction $transaction): ?string
    {
        $phone = self::normalizePhone((string) $transaction->customer_phone);

        if ($phone === null) {
            return null;
        }

        return 'https://wa.me/'.$phone.'?text='.rawurlencode(self::message($transaction));
    }

    public static function message(Transaction $transaction): string
    {
        $store = $transaction->store;
        $greetingName = $transaction->customer_name ?: 'Pelanggan';
        $receiptLines = implode("\n", ThermalReceiptFormatter::forTransaction($transaction));

        return "🧾 *{$store->name}*\n"
            ."Halo *{$greetingName}*, terima kasih sudah berbelanja! 🙏\n"
            ."Berikut struk transaksi Anda:\n\n"
            ."```\n{$receiptLines}\n```\n\n"
            .'Simpan pesan ini sebagai bukti pembayaran ya. Sampai jumpa lagi! 😊';
    }

    /**
     * wa.me needs a bare international number (country code, no leading
     * `0`/`+`/spaces). Assumes Indonesian numbers when no country code is
     * already present, since that's this app's userbase - a `0812...`
     * style number becomes `62812...`.
     */
    public static function normalizePhone(string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            return '62'.substr($digits, 1);
        }

        if (! str_starts_with($digits, '62')) {
            return '62'.$digits;
        }

        return $digits;
    }
}
