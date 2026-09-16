@props(['field', 'label', 'sortField' => '', 'sortDirection' => 'asc', 'class' => 'px-6 py-3'])
<th {{ $attributes->except('class') }} class="{{ $class }}">
    <button type="button" wire:click="sortBy('{{ $field }}')" class="inline-flex items-center gap-1 hover:text-slate-700 dark:hover:text-slate-200 transition">
        <span>{{ $label }}</span>
        @if ($sortField === $field)
            @if ($sortDirection === 'asc')
                <svg class="h-3 w-3 text-brand-500 dark:text-brand-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" /></svg>
            @else
                <svg class="h-3 w-3 text-brand-500 dark:text-brand-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
            @endif
        @else
            <svg class="h-3 w-3 text-slate-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" /></svg>
        @endif
    </button>
</th>
