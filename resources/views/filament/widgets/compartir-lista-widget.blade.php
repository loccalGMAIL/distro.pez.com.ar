@php
    $priceLists = \App\Models\PriceList::orderedForDisplay();
@endphp

<x-filament-widgets::widget>
    <x-filament::dropdown placement="bottom-start" class="block w-full">
        <x-slot name="trigger">
            <button
                type="button"
                class="group relative flex w-full items-center gap-4 overflow-hidden rounded-xl bg-white p-6 text-left shadow-sm ring-1 ring-gray-950/5 transition hover:-translate-y-0.5 hover:shadow-md hover:ring-emerald-600/50 dark:bg-gray-900 dark:ring-white/10"
            >
                <span class="absolute inset-x-0 top-0 h-1 bg-emerald-500"></span>

                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400">
                    <x-filament::icon
                        :icon="\Filament\Support\Icons\Heroicon::OutlinedShare"
                        class="h-6 w-6"
                    />
                </span>

                <span class="flex-1 text-base font-semibold text-gray-950 dark:text-white">
                    Compartir lista
                </span>

                <x-filament::icon
                    :icon="\Filament\Support\Icons\Heroicon::OutlinedArrowRight"
                    class="h-5 w-5 shrink-0 -translate-x-1 text-emerald-600 opacity-0 transition group-hover:translate-x-0 group-hover:opacity-100 dark:text-emerald-400"
                />
            </button>
        </x-slot>

        @if ($priceLists->isNotEmpty())
            @include('filament.price-lists.dropdown-items', ['priceLists' => $priceLists])
        @endif
    </x-filament::dropdown>
</x-filament-widgets::widget>
