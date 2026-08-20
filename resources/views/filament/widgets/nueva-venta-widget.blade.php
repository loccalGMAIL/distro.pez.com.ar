<x-filament-widgets::widget>
    <a
        href="{{ \App\Filament\Clusters\Sales\Resources\Sales\SaleResource::getUrl(parameters: ['action' => 'create']) }}"
        wire:navigate
        class="flex items-center gap-4 rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 transition hover:shadow-md hover:ring-amber-600/50 dark:bg-gray-900 dark:ring-white/10"
    >
        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400">
            <x-filament::icon
                :icon="\Filament\Support\Icons\Heroicon::OutlinedPlus"
                class="h-6 w-6"
            />
        </span>

        <span class="flex flex-col">
            <span class="text-base font-semibold text-gray-950 dark:text-white">
                Nueva venta
            </span>
            <span class="text-sm text-gray-500 dark:text-gray-400">
                Cargar una venta nueva
            </span>
        </span>
    </a>
</x-filament-widgets::widget>
