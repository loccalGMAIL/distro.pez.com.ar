<x-filament-widgets::widget>
    <a
        href="{{ \App\Filament\Clusters\Catalog\Resources\Products\ProductResource::getUrl() }}"
        wire:navigate
        class="group relative flex items-center gap-4 overflow-hidden rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 transition hover:-translate-y-0.5 hover:shadow-md hover:ring-indigo-600/50 dark:bg-gray-900 dark:ring-white/10"
    >
        <span class="absolute inset-x-0 top-0 h-1 bg-indigo-500"></span>

        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400">
            <x-filament::icon
                :icon="\Filament\Support\Icons\Heroicon::OutlinedRectangleStack"
                class="h-6 w-6"
            />
        </span>

        <span class="flex-1 text-base font-semibold text-gray-950 dark:text-white">
            Productos
        </span>

        <x-filament::icon
            :icon="\Filament\Support\Icons\Heroicon::OutlinedArrowRight"
            class="h-5 w-5 shrink-0 -translate-x-1 text-indigo-600 opacity-0 transition group-hover:translate-x-0 group-hover:opacity-100 dark:text-indigo-400"
        />
    </a>
</x-filament-widgets::widget>
