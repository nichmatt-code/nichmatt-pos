@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full pl-4 pr-4 py-2.5 rounded-lg text-start text-base font-medium text-brand-700 bg-brand-50 transition duration-150 ease-in-out'
            : 'block w-full pl-4 pr-4 py-2.5 rounded-lg text-start text-base font-medium text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
