@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'rounded-lg bg-emerald-50 border border-emerald-100 px-4 py-3 font-medium text-sm text-emerald-700 dark:bg-emerald-950/40 dark:border-emerald-900/60 dark:text-emerald-300']) }}>
        {{ $status }}
    </div>
@endif
