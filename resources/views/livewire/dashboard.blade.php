<div class="space-y-6">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
        <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6">
            <div class="text-sm font-medium text-gray-500">Omzet Hari Ini</div>
            <div class="mt-1 text-3xl font-semibold text-gray-900">Rp {{ number_format($todayOmzet, 0, ',', '.') }}</div>
            <div class="mt-1 text-sm text-gray-500">{{ $todayCount }} transaksi</div>
        </div>

        <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6">
            <div class="text-sm font-medium text-gray-500">Akses Cepat</div>
            <div class="mt-3 flex flex-wrap gap-2">
                <a href="{{ route('pos') }}" wire:navigate class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                    Buka Kasir
                </a>
                <a href="{{ route('products.index') }}" wire:navigate class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">
                    Kelola Produk
                </a>
                <a href="{{ route('reports.sales') }}" wire:navigate class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">
                    Laporan
                </a>
            </div>
        </div>
    </div>

    <div class="bg-white overflow-hidden shadow-sm rounded-lg">
        <div class="p-6">
            <h3 class="text-lg font-medium text-gray-900">Stok Menipis</h3>

            @if ($lowStockProducts->isEmpty())
                <p class="mt-2 text-sm text-gray-500">Tidak ada produk dengan stok menipis.</p>
            @else
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="pb-2 pr-4">Produk</th>
                                <th class="pb-2 pr-4">Sisa Stok</th>
                                <th class="pb-2">Satuan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($lowStockProducts as $product)
                                <tr>
                                    <td class="py-2 pr-4 text-gray-900">{{ $product->name }}</td>
                                    <td class="py-2 pr-4 text-red-600 font-semibold">{{ $product->stock_qty }}</td>
                                    <td class="py-2 text-gray-500">{{ $product->unit }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
