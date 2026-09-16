<?php

namespace App\Console\Commands;

use App\Models\StoreSubscriptionPayment;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Midtrans\Transaction;

#[Signature('midtrans:reconcile-payments {order_id? : Reconcile only this order ID instead of every pending payment}')]
#[Description('Poll Midtrans for the real status of pending subscription payments, in case their notification webhook was never delivered')]
class ReconcilePendingSubscriptionPayments extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $query = StoreSubscriptionPayment::withoutGlobalScopes()->where('status', 'pending');

        if ($orderId = $this->argument('order_id')) {
            $query->where('order_id', $orderId);
        }

        $payments = $query->get();

        if ($payments->isEmpty()) {
            $this->info('No pending payments to reconcile.');

            return self::SUCCESS;
        }

        foreach ($payments as $payment) {
            try {
                $status = Transaction::status($payment->order_id);
            } catch (\Exception $e) {
                $this->warn("Could not fetch status for {$payment->order_id}: {$e->getMessage()}");

                continue;
            }

            $transactionStatus = $status->transaction_status ?? null;

            $payment->applyMidtransStatus(
                $transactionStatus,
                $status->fraud_status ?? null,
                $status->payment_type ?? null,
                $status->transaction_id ?? null,
            );

            $this->line("{$payment->order_id}: {$transactionStatus} -> payment status is now {$payment->status}");
        }

        return self::SUCCESS;
    }
}
