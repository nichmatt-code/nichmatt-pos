@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-slate-200 bg-slate-50/60 focus:bg-white focus:border-brand-500 focus:ring-brand-500 rounded-lg shadow-sm text-slate-900 placeholder:text-slate-400 transition']) }}>
