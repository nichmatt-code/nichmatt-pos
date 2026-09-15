@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full pl-4 pr-4 py-2.5 rounded-lg text-start text-base font-medium text-brand-700 bg-brand-50 dark:text-brand-300 dark:bg-brand-500/10 transition duration-150 ease-in-out'
            : 'block w-full pl-4 pr-4 py-2.5 rounded-lg text-start text-base font-medium text-slate-600 hover:text-slate-900 hover:bg-slate-100 dark:text-slate-400 dark:hover:text-slate-100 dark:hover:bg-slate-800 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
