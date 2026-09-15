<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-slate-200 rounded-lg font-medium text-sm text-slate-700 shadow-sm hover:bg-slate-50 hover:border-slate-300 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 disabled:opacity-40 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
