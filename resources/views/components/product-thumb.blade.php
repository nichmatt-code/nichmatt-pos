@props(['product', 'class' => 'h-24 w-full rounded-lg'])

@if ($product->imageUrl())
    <img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}" {{ $attributes->merge(['class' => $class.' object-cover']) }}>
@else
    <span {{ $attributes->merge(['class' => $class.' flex items-center justify-center bg-slate-100 dark:bg-slate-800 p-2']) }}>
        <img src="{{ asset('images/logo.png') }}" alt="" class="max-h-full max-w-full object-contain grayscale opacity-30 dark:opacity-20">
    </span>
@endif
