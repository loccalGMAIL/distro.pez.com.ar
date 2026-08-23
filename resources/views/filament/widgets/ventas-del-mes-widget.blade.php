<x-filament-widgets::widget>
    <div class="relative flex items-center gap-4 overflow-hidden rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <span class="absolute inset-x-0 top-0 h-1 bg-teal-500"></span>

        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-teal-50 text-teal-600 dark:bg-teal-500/10 dark:text-teal-400">
            <x-filament::icon
                :icon="\Filament\Support\Icons\Heroicon::OutlinedBanknotes"
                class="h-6 w-6"
            />
        </span>

        <span class="flex flex-1 flex-col">
            <span class="text-base font-semibold text-gray-950 dark:text-white">
                {{ $total }}
            </span>
            <span class="text-sm text-gray-500 dark:text-gray-400">
                Ventas del mes
            </span>
        </span>
    </div>
</x-filament-widgets::widget>
