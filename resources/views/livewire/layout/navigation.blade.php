<?php

use App\Livewire\Actions\Logout;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component
{
    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }

    /**
     * Switch the active session to another account this browser has
     * already authenticated as, without asking for a password again.
     */
    public function switchToAccount(int $userId): void
    {
        if (! in_array($userId, session('linked_accounts', []), true)) {
            abort(403);
        }

        $user = User::find($userId);

        if (! $user || ! $user->is_active) {
            $this->forgetLinkedAccount($userId);

            return;
        }

        Auth::loginUsingId($userId);
        session()->regenerate();

        $this->redirect(route('pos', absolute: false), navigate: true);
    }

    /**
     * Remove an account from this browser's switcher list. If it's the
     * active one, switch to another linked account or log out entirely.
     */
    public function forgetLinkedAccount(int $userId): void
    {
        $ids = array_values(array_diff(session('linked_accounts', []), [$userId]));
        session(['linked_accounts' => $ids]);

        if ($userId !== Auth::id()) {
            return;
        }

        if (empty($ids)) {
            $this->logout(app(Logout::class));

            return;
        }

        Auth::loginUsingId(array_pop($ids));
        session()->regenerate();
        $this->redirect(route('pos', absolute: false), navigate: true);
    }
}; ?>

<nav x-data="{ open: false }" class="sticky top-0 z-40 bg-white/85 dark:bg-slate-900/85 backdrop-blur border-b border-slate-200/70 dark:border-slate-800/70">
    @php
        $user = auth()->user();
        $inOperational = request()->routeIs('pos') || request()->routeIs('preparation.index') || request()->routeIs('stock-opname.*');
        $inProduct = request()->routeIs('products.index') || request()->routeIs('categories.index') || request()->routeIs('tags.index') || request()->routeIs('inventory.index');
        $inAdministration = request()->routeIs('team.index') || request()->routeIs('branch.settings') || request()->routeIs('customers.index');
        $hasProduct = $user->hasPermission(\App\Permission::Products)
            || $user->hasPermission(\App\Permission::Categories)
            || $user->hasPermission(\App\Permission::Inventory);
        $hasAdministration = $user->hasPermission(\App\Permission::Employees)
            || $user->hasPermission(\App\Permission::StoreSettings)
            || $user->hasPermission(\App\Permission::Customers);
    @endphp

    <!-- Top row: logo, store name, and account -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-14">
            <!-- Logo -->
            <a href="{{ route('pos') }}" wire:navigate class="shrink-0 flex items-center">
                <x-application-logo class="block h-8 w-auto" />
            </a>

            <!-- Settings -->
            <div class="hidden sm:flex sm:items-center sm:gap-2">
                <span class="text-sm text-slate-400 dark:text-slate-500 mr-2 truncate max-w-[220px]" title="{{ auth()->user()->store?->name }}">{{ auth()->user()->store?->name }}</span>

                <x-fullscreen-toggle />
                <x-theme-toggle />

                <x-dropdown align="right" width="52">
                    <x-slot name="trigger">
                        <button class="flex items-center gap-2 rounded-full pl-1 pr-3 py-1 hover:bg-slate-100 dark:hover:bg-slate-800 transition focus:outline-none">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-600 text-xs font-semibold text-white">
                                {{ Str::of(auth()->user()->name)->substr(0, 1)->upper() }}
                            </span>
                            <span class="text-sm font-medium text-slate-700 dark:text-slate-300" x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></span>
                            <svg class="h-4 w-4 text-slate-400 dark:text-slate-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile')" wire:navigate>
                            {{ __('Profil') }}
                        </x-dropdown-link>

                        @php $linkedAccounts = \App\Models\User::linkedAccounts()->where('id', '!=', auth()->id()); @endphp
                        @if ($linkedAccounts->isNotEmpty())
                            <div class="my-1 border-t border-slate-100 dark:border-slate-700"></div>
                            <p class="px-4 pt-1 pb-1 text-xs font-medium uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('Ganti Akun') }}</p>
                            @foreach ($linkedAccounts as $account)
                                <button type="button" wire:click="switchToAccount({{ $account->id }})" class="w-full flex items-center gap-2 px-4 py-2 text-start text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800">
                                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-slate-200 dark:bg-slate-700 text-[10px] font-semibold text-slate-600 dark:text-slate-300">
                                        {{ Str::of($account->name)->substr(0, 1)->upper() }}
                                    </span>
                                    <span class="truncate">
                                        <span class="block truncate">{{ $account->name }}</span>
                                        <span class="block truncate text-xs text-slate-400 dark:text-slate-500">{{ $account->email }}</span>
                                    </span>
                                </button>
                            @endforeach
                        @endif

                        <div class="my-1 border-t border-slate-100 dark:border-slate-700"></div>

                        <div class="flex items-center justify-between gap-2 px-4 py-2">
                            <x-dropdown-link :href="route('accounts.add')" wire:navigate class="!px-0 !py-0">
                                {{ __('+ Tambah Akun Lain') }}
                            </x-dropdown-link>
                            <a href="{{ route('auth.google.redirect') }}" title="{{ __('Tambah akun dengan Google') }}" class="shrink-0 rounded-md p-1 hover:bg-slate-100 dark:hover:bg-slate-700">
                                <svg class="w-4 h-4" viewBox="0 0 24 24"><path fill="#4285F4" d="M23.52 12.27c0-.85-.08-1.67-.22-2.45H12v4.64h6.47a5.53 5.53 0 0 1-2.4 3.63v3h3.88c2.27-2.09 3.57-5.17 3.57-8.82Z"/><path fill="#34A853" d="M12 24c3.24 0 5.96-1.07 7.95-2.91l-3.88-3a7.4 7.4 0 0 1-11-3.9H.98v3.09A12 12 0 0 0 12 24Z"/><path fill="#FBBC05" d="M5.07 14.19a7.2 7.2 0 0 1 0-4.38V6.72H.98a12 12 0 0 0 0 10.56l4.09-3.09Z"/><path fill="#EA4335" d="M12 4.77c1.76 0 3.35.6 4.6 1.79l3.44-3.44C17.95 1.19 15.24 0 12 0A12 12 0 0 0 .98 6.72l4.09 3.09A7.16 7.16 0 0 1 12 4.77Z"/></svg>
                            </a>
                        </div>

                        <!-- Authentication -->
                        <button wire:click="logout" class="w-full text-start">
                            <x-dropdown-link>
                                {{ __('Keluar') }}
                            </x-dropdown-link>
                        </button>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center gap-1 sm:hidden">
                <x-fullscreen-toggle />
                <x-theme-toggle />
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:text-slate-500 dark:hover:text-slate-300 dark:hover:bg-slate-800 focus:outline-none transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Bottom row: navigation menu -->
    <div class="hidden sm:block border-t border-slate-200/70 dark:border-slate-800/70">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-wrap items-center justify-center gap-1 py-1.5">
                @if ($user->hasPermission(\App\Permission::Dashboard))
                        <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" wire:navigate>
                            {{ __('Dashboard') }}
                        </x-nav-link>
                    @endif

                    <x-nav-dropdown label="{{ __('Kasir') }}" :active="$inOperational">
                        <x-dropdown-link :href="route('pos')" wire:navigate>
                            {{ __('Kasir') }}
                        </x-dropdown-link>
                        @if ($user->hasPermission(\App\Permission::Preparation))
                            <x-dropdown-link :href="route('preparation.index')" wire:navigate>
                                {{ __('Persiapan') }}
                            </x-dropdown-link>
                        @endif
                        @if ($user->hasPermission(\App\Permission::StockOpname))
                            <x-dropdown-link :href="route('stock-opname.index')" wire:navigate>
                                {{ __('Stock Opname') }}
                            </x-dropdown-link>
                        @endif
                    </x-nav-dropdown>

                    @if ($hasProduct)
                        <x-nav-dropdown label="{{ __('Product') }}" :active="$inProduct">
                            @if ($user->hasPermission(\App\Permission::Products))
                                <x-dropdown-link :href="route('products.index')" wire:navigate>
                                    {{ __('Produk') }}
                                </x-dropdown-link>
                            @endif
                            @if ($user->hasPermission(\App\Permission::Categories))
                                <x-dropdown-link :href="route('categories.index')" wire:navigate>
                                    {{ __('Kategori') }}
                                </x-dropdown-link>
                            @endif
                            @if ($user->hasPermission(\App\Permission::Inventory))
                                <x-dropdown-link :href="route('inventory.index')" wire:navigate>
                                    {{ __('Inventory') }}
                                </x-dropdown-link>
                            @endif
                            @if ($user->hasPermission(\App\Permission::Products))
                                <x-dropdown-link :href="route('tags.index')" wire:navigate>
                                    {{ __('Tags') }}
                                </x-dropdown-link>
                            @endif
                        </x-nav-dropdown>
                    @endif

                    @if ($hasAdministration)
                        <x-nav-dropdown label="{{ __('Administrasi') }}" :active="$inAdministration">
                            @if ($user->hasPermission(\App\Permission::Customers))
                                <x-dropdown-link :href="route('customers.index')" wire:navigate>
                                    {{ __('Pelanggan') }}
                                </x-dropdown-link>
                            @endif
                            @if ($user->hasPermission(\App\Permission::Employees))
                                <x-dropdown-link :href="route('team.index')" wire:navigate>
                                    {{ __('Karyawan') }}
                                </x-dropdown-link>
                            @endif
                            @if ($user->hasPermission(\App\Permission::StoreSettings))
                                <x-dropdown-link :href="route('branch.settings')" wire:navigate>
                                    {{ __('Cabang') }}
                                </x-dropdown-link>
                            @endif
                        </x-nav-dropdown>
                    @endif

                    @if ($user->hasPermission(\App\Permission::Reports))
                        <x-nav-link :href="route('reports.sales')" :active="request()->routeIs('reports.sales')" wire:navigate>
                            {{ __('Laporan') }}
                        </x-nav-link>
                    @endif
                    @if ($user->isOwner())
                        <x-nav-link :href="route('billing.subscribe')" :active="request()->routeIs('billing.subscribe')" wire:navigate>
                            {{ __('Langganan') }}
                        </x-nav-link>
                    @endif

                    @if ($user->isDeveloper())
                        @php $inDeveloper = request()->routeIs('developer.*'); @endphp
                        <x-dropdown align="left" width="48">
                            <x-slot name="trigger">
                                <button type="button" class="inline-flex items-center gap-1 px-3 py-2 rounded-lg text-sm font-medium border {{ $inDeveloper ? 'text-violet-700 bg-violet-50 border-violet-200 dark:text-violet-300 dark:bg-violet-500/10 dark:border-violet-800' : 'text-violet-600 border-violet-200 hover:bg-violet-50 dark:text-violet-400 dark:border-violet-900 dark:hover:bg-violet-500/10' }} transition duration-150 ease-in-out">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                    {{ __('Developer') }}
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                                </button>
                            </x-slot>
                            <x-slot name="content">
                                <x-dropdown-link :href="route('developer.stores.index')" wire:navigate>
                                    {{ __('Semua Toko') }}
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('developer.pricing')" wire:navigate>
                                    {{ __('Harga & Promo') }}
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('developer.team')" wire:navigate>
                                    {{ __('Akses Developer') }}
                                </x-dropdown-link>
                            </x-slot>
                        </x-dropdown>
                    @endif
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden border-t border-slate-200/70 dark:border-slate-800/70">
        <div class="px-3 pt-3 pb-3 space-y-1">
            @if ($user->hasPermission(\App\Permission::Dashboard))
                <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" wire:navigate>
                    {{ __('Dashboard') }}
                </x-responsive-nav-link>
            @endif

            <p class="px-4 pt-3 pb-1 text-xs font-medium uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('Kasir') }}</p>
            <x-responsive-nav-link :href="route('pos')" :active="request()->routeIs('pos')" wire:navigate>
                {{ __('Kasir') }}
            </x-responsive-nav-link>
            @if ($user->hasPermission(\App\Permission::Preparation))
                <x-responsive-nav-link :href="route('preparation.index')" :active="request()->routeIs('preparation.index')" wire:navigate>
                    {{ __('Persiapan') }}
                </x-responsive-nav-link>
            @endif
            @if ($user->hasPermission(\App\Permission::StockOpname))
                <x-responsive-nav-link :href="route('stock-opname.index')" :active="request()->routeIs('stock-opname.*')" wire:navigate>
                    {{ __('Stock Opname') }}
                </x-responsive-nav-link>
            @endif

            @if ($hasProduct)
                <p class="px-4 pt-3 pb-1 text-xs font-medium uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('Product') }}</p>
                @if ($user->hasPermission(\App\Permission::Products))
                    <x-responsive-nav-link :href="route('products.index')" :active="request()->routeIs('products.index')" wire:navigate>
                        {{ __('Produk') }}
                    </x-responsive-nav-link>
                @endif
                @if ($user->hasPermission(\App\Permission::Categories))
                    <x-responsive-nav-link :href="route('categories.index')" :active="request()->routeIs('categories.index')" wire:navigate>
                        {{ __('Kategori') }}
                    </x-responsive-nav-link>
                @endif
                @if ($user->hasPermission(\App\Permission::Inventory))
                    <x-responsive-nav-link :href="route('inventory.index')" :active="request()->routeIs('inventory.index')" wire:navigate>
                        {{ __('Inventory') }}
                    </x-responsive-nav-link>
                @endif
                @if ($user->hasPermission(\App\Permission::Products))
                    <x-responsive-nav-link :href="route('tags.index')" :active="request()->routeIs('tags.index')" wire:navigate>
                        {{ __('Tags') }}
                    </x-responsive-nav-link>
                @endif
            @endif

            @if ($hasAdministration)
                <p class="px-4 pt-3 pb-1 text-xs font-medium uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('Administrasi') }}</p>
                @if ($user->hasPermission(\App\Permission::Customers))
                    <x-responsive-nav-link :href="route('customers.index')" :active="request()->routeIs('customers.index')" wire:navigate>
                        {{ __('Pelanggan') }}
                    </x-responsive-nav-link>
                @endif
                @if ($user->hasPermission(\App\Permission::Employees))
                    <x-responsive-nav-link :href="route('team.index')" :active="request()->routeIs('team.index')" wire:navigate>
                        {{ __('Karyawan') }}
                    </x-responsive-nav-link>
                @endif
                @if ($user->hasPermission(\App\Permission::StoreSettings))
                    <x-responsive-nav-link :href="route('branch.settings')" :active="request()->routeIs('branch.settings')" wire:navigate>
                        {{ __('Cabang') }}
                    </x-responsive-nav-link>
                @endif
            @endif

            @if ($user->hasPermission(\App\Permission::Reports))
                <x-responsive-nav-link :href="route('reports.sales')" :active="request()->routeIs('reports.sales')" wire:navigate>
                    {{ __('Laporan') }}
                </x-responsive-nav-link>
            @endif
            @if ($user->isOwner())
                <x-responsive-nav-link :href="route('billing.subscribe')" :active="request()->routeIs('billing.subscribe')" wire:navigate>
                    {{ __('Langganan') }}
                </x-responsive-nav-link>
            @endif

            @if ($user->isDeveloper())
                <div class="mt-1 pt-1 border-t border-slate-200 dark:border-slate-700">
                    <p class="px-4 pt-2 pb-1 text-xs font-semibold uppercase tracking-wider text-violet-500 dark:text-violet-400">{{ __('Developer') }}</p>
                    <x-responsive-nav-link :href="route('developer.stores.index')" :active="request()->routeIs('developer.stores.*')" wire:navigate>
                        {{ __('Semua Toko') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('developer.pricing')" :active="request()->routeIs('developer.pricing')" wire:navigate>
                        {{ __('Harga & Promo') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('developer.team')" :active="request()->routeIs('developer.team')" wire:navigate>
                        {{ __('Akses Developer') }}
                    </x-responsive-nav-link>
                </div>
            @endif
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-3 pb-3 border-t border-slate-200/70 dark:border-slate-800/70">
            <div class="px-4 flex items-center gap-3">
                <span class="flex h-9 w-9 items-center justify-center rounded-full bg-brand-600 text-sm font-semibold text-white">
                    {{ Str::of(auth()->user()->name)->substr(0, 1)->upper() }}
                </span>
                <div>
                    <div class="font-medium text-base text-slate-800 dark:text-slate-200" x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></div>
                    <div class="text-sm text-slate-500 dark:text-slate-400">{{ auth()->user()->email }}</div>
                </div>
            </div>

            <div class="mt-3 px-3 space-y-1">
                <x-responsive-nav-link :href="route('profile')" wire:navigate>
                    {{ __('Profil') }}
                </x-responsive-nav-link>

                @php $mobileLinkedAccounts = \App\Models\User::linkedAccounts()->where('id', '!=', auth()->id()); @endphp
                @if ($mobileLinkedAccounts->isNotEmpty())
                    <p class="px-4 pt-2 pb-1 text-xs font-medium uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('Ganti Akun') }}</p>
                    @foreach ($mobileLinkedAccounts as $account)
                        <button type="button" wire:click="switchToAccount({{ $account->id }})" class="w-full flex items-center gap-2 rounded-lg px-4 py-2 text-start text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-slate-200 dark:bg-slate-700 text-[10px] font-semibold text-slate-600 dark:text-slate-300">
                                {{ Str::of($account->name)->substr(0, 1)->upper() }}
                            </span>
                            <span class="truncate">{{ $account->name }}</span>
                        </button>
                    @endforeach
                @endif

                <div class="flex items-center justify-between gap-2 px-4">
                    <x-responsive-nav-link :href="route('accounts.add')" wire:navigate class="!px-0">
                        {{ __('+ Tambah Akun Lain') }}
                    </x-responsive-nav-link>
                    <a href="{{ route('auth.google.redirect') }}" title="{{ __('Tambah akun dengan Google') }}" class="shrink-0 rounded-md p-1 hover:bg-slate-100 dark:hover:bg-slate-800">
                        <svg class="w-4 h-4" viewBox="0 0 24 24"><path fill="#4285F4" d="M23.52 12.27c0-.85-.08-1.67-.22-2.45H12v4.64h6.47a5.53 5.53 0 0 1-2.4 3.63v3h3.88c2.27-2.09 3.57-5.17 3.57-8.82Z"/><path fill="#34A853" d="M12 24c3.24 0 5.96-1.07 7.95-2.91l-3.88-3a7.4 7.4 0 0 1-11-3.9H.98v3.09A12 12 0 0 0 12 24Z"/><path fill="#FBBC05" d="M5.07 14.19a7.2 7.2 0 0 1 0-4.38V6.72H.98a12 12 0 0 0 0 10.56l4.09-3.09Z"/><path fill="#EA4335" d="M12 4.77c1.76 0 3.35.6 4.6 1.79l3.44-3.44C17.95 1.19 15.24 0 12 0A12 12 0 0 0 .98 6.72l4.09 3.09A7.16 7.16 0 0 1 12 4.77Z"/></svg>
                    </a>
                </div>

                <!-- Authentication -->
                <button wire:click="logout" class="w-full text-start">
                    <x-responsive-nav-link>
                        {{ __('Keluar') }}
                    </x-responsive-nav-link>
                </button>
            </div>
        </div>
    </div>
</nav>
