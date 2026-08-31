@php
    $colors = [
        ['border-rose-300 dark:border-rose-500/40', 'bg-rose-50 dark:bg-rose-500/10'],
        ['border-amber-300 dark:border-amber-500/40', 'bg-amber-50 dark:bg-amber-500/10'],
        ['border-emerald-300 dark:border-emerald-500/40', 'bg-emerald-50 dark:bg-emerald-500/10'],
        ['border-sky-300 dark:border-sky-500/40', 'bg-sky-50 dark:bg-sky-500/10'],
        ['border-violet-300 dark:border-violet-500/40', 'bg-violet-50 dark:bg-violet-500/10'],
        ['border-fuchsia-300 dark:border-fuchsia-500/40', 'bg-fuchsia-50 dark:bg-fuchsia-500/10'],
        ['border-lime-300 dark:border-lime-500/40', 'bg-lime-50 dark:bg-lime-500/10'],
        ['border-cyan-300 dark:border-cyan-500/40', 'bg-cyan-50 dark:bg-cyan-500/10'],
    ];

    [$border, $background] = $colors[$index % count($colors)];
@endphp

<button
    type="button"
    wire:click="mountAction('addProductToSale', { product: {{ $product->id }} })"
    class="flex flex-col items-start gap-1 rounded-lg border {{ $border }} {{ $background }} px-3 py-2 text-left transition hover:brightness-95 dark:hover:brightness-125"
>
    <span class="text-lg font-medium text-gray-950 sm:text-sm dark:text-white">
        {{ $product->nombre }}
    </span>
    <span class="text-base text-gray-600 sm:text-xs dark:text-gray-400">
        ${{ number_format((float) $product->precioParaLista($priceListId), 2, ',', '.') }}
    </span>
</button>
