<?php

use App\Livewire\Actions\Logout;
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
}; ?>

<nav x-data="{ open: false }" class="sticky top-0 z-40 bg-white/85 backdrop-blur border-b border-slate-200/70">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center gap-8">
                <!-- Logo -->
                <a href="{{ route('dashboard') }}" wire:navigate class="shrink-0 flex items-center">
                    <x-application-logo class="block h-8 w-auto" />
                </a>

                <!-- Navigation Links -->
                <div class="hidden sm:flex sm:items-center sm:gap-1">
                    @if (auth()->user()->isOwner())
                        <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" wire:navigate>
                            {{ __('Dashboard') }}
                        </x-nav-link>
                    @endif

                    <x-nav-link :href="route('pos')" :active="request()->routeIs('pos')" wire:navigate>
                        {{ __('Kasir') }}
                    </x-nav-link>

                    @if (auth()->user()->isOwner())
                        <x-nav-link :href="route('products.index')" :active="request()->routeIs('products.index')" wire:navigate>
                            {{ __('Produk') }}
                        </x-nav-link>
                        <x-nav-link :href="route('categories.index')" :active="request()->routeIs('categories.index')" wire:navigate>
                            {{ __('Kategori') }}
                        </x-nav-link>
                        <x-nav-link :href="route('reports.sales')" :active="request()->routeIs('reports.sales')" wire:navigate>
                            {{ __('Laporan') }}
                        </x-nav-link>
                        <x-nav-link :href="route('billing.subscribe')" :active="request()->routeIs('billing.subscribe')" wire:navigate>
                            {{ __('Langganan') }}
                        </x-nav-link>
                    @endif
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:gap-4">
                <span class="text-sm text-slate-400">{{ auth()->user()->store?->name }}</span>

                <x-dropdown align="right" width="52">
                    <x-slot name="trigger">
                        <button class="flex items-center gap-2 rounded-full pl-1 pr-3 py-1 hover:bg-slate-100 transition focus:outline-none">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-600 text-xs font-semibold text-white">
                                {{ Str::of(auth()->user()->name)->substr(0, 1)->upper() }}
                            </span>
                            <span class="text-sm font-medium text-slate-700" x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></span>
                            <svg class="h-4 w-4 text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile')" wire:navigate>
                            {{ __('Profil') }}
                        </x-dropdown-link>

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
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 focus:outline-none transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden border-t border-slate-200/70">
        <div class="px-3 pt-3 pb-3 space-y-1">
            @if (auth()->user()->isOwner())
                <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" wire:navigate>
                    {{ __('Dashboard') }}
                </x-responsive-nav-link>
            @endif

            <x-responsive-nav-link :href="route('pos')" :active="request()->routeIs('pos')" wire:navigate>
                {{ __('Kasir') }}
            </x-responsive-nav-link>

            @if (auth()->user()->isOwner())
                <x-responsive-nav-link :href="route('products.index')" :active="request()->routeIs('products.index')" wire:navigate>
                    {{ __('Produk') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('categories.index')" :active="request()->routeIs('categories.index')" wire:navigate>
                    {{ __('Kategori') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('reports.sales')" :active="request()->routeIs('reports.sales')" wire:navigate>
                    {{ __('Laporan') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('billing.subscribe')" :active="request()->routeIs('billing.subscribe')" wire:navigate>
                    {{ __('Langganan') }}
                </x-responsive-nav-link>
            @endif
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-3 pb-3 border-t border-slate-200/70">
            <div class="px-4 flex items-center gap-3">
                <span class="flex h-9 w-9 items-center justify-center rounded-full bg-brand-600 text-sm font-semibold text-white">
                    {{ Str::of(auth()->user()->name)->substr(0, 1)->upper() }}
                </span>
                <div>
                    <div class="font-medium text-base text-slate-800" x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></div>
                    <div class="text-sm text-slate-500">{{ auth()->user()->email }}</div>
                </div>
            </div>

            <div class="mt-3 px-3 space-y-1">
                <x-responsive-nav-link :href="route('profile')" wire:navigate>
                    {{ __('Profil') }}
                </x-responsive-nav-link>

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
