<div class="space-y-6">
    <div class="bg-white rounded-lg shadow-sm p-4 flex flex-wrap items-end gap-4">
        <div>
            <x-input-label for="startDate" value="Dari Tanggal" />
            <input wire:model.live="startDate" id="startDate" type="date" class="mt-1 border-gray-300 rounded-md shadow-sm" />
        </div>
        <div>
            <x-input-label for="endDate" value="Sampai Tanggal" />
            <input wire:model.live="endDate" id="endDate" type="date" class="mt-1 border-gray-300 rounded-md shadow-sm" />
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="text-sm font-medium text-gray-500">Total Omzet</div>
            <div class="mt-1 text-2xl font-semibold text-gray-900">Rp {{ number_format($totalOmzet, 0, ',', '.') }}</div>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="text-sm font-medium text-gray-500">Jumlah Transaksi</div>
            <div class="mt-1 text-2xl font-semibold text-gray-900">{{ $totalTransactions }}</div>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="text-sm font-medium text-gray-500">Estimasi Laba</div>
            <div class="mt-1 text-2xl font-semibold text-gray-900">Rp {{ number_format($totalProfit, 0, ',', '.') }}</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="p-6 pb-0">
                <h3 class="text-lg font-medium text-gray-900">Produk Terlaris</h3>
            </div>
            <div class="p-6 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr class="text-left text-gray-500">
                            <th class="pb-2 pr-4">Produk</th>
                            <th class="pb-2 pr-4">Terjual</th>
                            <th class="pb-2">Omzet</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($bestSellers as $row)
                            <tr>
                                <td class="py-2 pr-4 text-gray-900">{{ $row['name'] }}</td>
                                <td class="py-2 pr-4 text-gray-500">{{ $row['qty'] }}</td>
                                <td class="py-2 text-gray-500">Rp {{ number_format($row['revenue'], 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="py-6 text-center text-gray-500">Belum ada penjualan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="p-6 pb-0">
                <h3 class="text-lg font-medium text-gray-900">Riwayat Transaksi</h3>
            </div>
            <div class="p-6 overflow-x-auto max-h-96 overflow-y-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr class="text-left text-gray-500">
                            <th class="pb-2 pr-4">No. Transaksi</th>
                            <th class="pb-2 pr-4">Waktu</th>
                            <th class="pb-2">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($transactions as $transaction)
                            <tr>
                                <td class="py-2 pr-4">
                                    <a href="{{ route('transactions.receipt', $transaction) }}" target="_blank" class="text-indigo-600 hover:text-indigo-800">
                                        {{ $transaction->transaction_no }}
                                    </a>
                                </td>
                                <td class="py-2 pr-4 text-gray-500">{{ $transaction->created_at->format('d/m/Y H:i') }}</td>
                                <td class="py-2 text-gray-900">Rp {{ number_format($transaction->total, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="py-6 text-center text-gray-500">Belum ada transaksi.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
