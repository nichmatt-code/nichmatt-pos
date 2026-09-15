<div class="space-y-6">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div class="bg-white border border-slate-200/70 shadow-card rounded-2xl p-6">
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V6m0 2v8m0 0v2m0-2c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </span>
                <div class="text-sm font-medium text-slate-500">{{ __('Omzet Hari Ini') }}</div>
            </div>
            <div class="mt-4 text-3xl font-semibold text-slate-900 tracking-tight">Rp {{ number_format($todayOmzet, 0, ',', '.') }}</div>
            <div class="mt-1 text-sm text-slate-500">{{ $todayCount }} {{ __('transaksi') }}</div>
        </div>

        <div class="bg-white border border-slate-200/70 shadow-card rounded-2xl p-6">
            <div class="text-sm font-medium text-slate-500">{{ __('Akses Cepat') }}</div>
            <div class="mt-4 flex flex-wrap gap-2">
                <a href="{{ route('pos') }}" wire:navigate class="inline-flex items-center px-4 py-2 bg-brand-600 rounded-lg font-medium text-sm text-white shadow-sm hover:bg-brand-700 transition">
                    {{ __('Buka Kasir') }}
                </a>
                <a href="{{ route('products.index') }}" wire:navigate class="inline-flex items-center px-4 py-2 bg-white border border-slate-200 rounded-lg font-medium text-sm text-slate-700 hover:bg-slate-50 transition">
                    {{ __('Kelola Produk') }}
                </a>
                <a href="{{ route('reports.sales') }}" wire:navigate class="inline-flex items-center px-4 py-2 bg-white border border-slate-200 rounded-lg font-medium text-sm text-slate-700 hover:bg-slate-50 transition">
                    {{ __('Laporan') }}
                </a>
            </div>
        </div>
    </div>

    <div class="bg-white border border-slate-200/70 shadow-card rounded-2xl">
        <div class="p-6">
            <h3 class="text-base font-semibold text-slate-900">{{ __('Stok Menipis') }}</h3>

            @if ($lowStockProducts->isEmpty())
                <p class="mt-3 text-sm text-slate-500">{{ __('Tidak ada produk dengan stok menipis.') }}</p>
            @else
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs font-medium uppercase tracking-wide text-slate-400">
                                <th class="pb-3 pr-4">{{ __('Produk') }}</th>
                                <th class="pb-3 pr-4">{{ __('Sisa Stok') }}</th>
                                <th class="pb-3">{{ __('Satuan') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($lowStockProducts as $product)
                                <tr>
                                    <td class="py-2.5 pr-4 text-slate-900">{{ $product->name }}</td>
                                    <td class="py-2.5 pr-4">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-600">
                                            {{ $product->stock_qty }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 text-slate-500">{{ $product->unit }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
