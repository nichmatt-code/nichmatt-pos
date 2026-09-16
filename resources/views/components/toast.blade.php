@props(['on'])

<div
    x-data="{ show: false, message: '', timeout: null }"
    x-on:{{ $on }}.window="clearTimeout(timeout); message = $event.detail.message; show = true; timeout = setTimeout(() => show = false, 2500)"
    x-show="show"
    x-transition:enter="ease-out duration-200"
    x-transition:enter-start="opacity-0 translate-y-2"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="ease-in duration-150"
    x-transition:leave-start="opacity-100 translate-y-0"
    x-transition:leave-end="opacity-0 translate-y-2"
    style="display: none;"
    class="fixed bottom-6 right-6 z-[60] flex items-center gap-2 px-4 py-3 rounded-xl bg-slate-900 dark:bg-slate-100 text-white dark:text-slate-900 text-sm font-medium shadow-lg"
>
    <svg class="h-4 w-4 text-emerald-400 dark:text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
    <span x-text="message"></span>
</div>
