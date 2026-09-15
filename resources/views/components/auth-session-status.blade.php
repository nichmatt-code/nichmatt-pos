@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'rounded-lg bg-emerald-50 border border-emerald-100 px-4 py-3 font-medium text-sm text-emerald-700']) }}>
        {{ $status }}
    </div>
@endif
