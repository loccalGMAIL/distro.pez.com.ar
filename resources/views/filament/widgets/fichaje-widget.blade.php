<x-filament-widgets::widget>
    <div class="relative flex items-center gap-4 overflow-hidden rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <span class="absolute inset-x-0 top-0 h-1 bg-amber-500"></span>

        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400">
            <x-filament::icon
                :icon="\Filament\Support\Icons\Heroicon::OutlinedClock"
                class="h-6 w-6"
            />
        </span>

        <span class="flex flex-1 flex-col">
            @if ($openEntry)
                <span class="text-base font-semibold text-gray-950 dark:text-white">
                    Trabajando desde {{ $openEntry->started_at->format('H:i') }}
                </span>
                <span class="text-sm text-gray-500 dark:text-gray-400">
                    Jornada en curso
                </span>
            @else
                <span class="text-base font-semibold text-gray-950 dark:text-white">
                    Jornada no iniciada
                </span>
                <span class="text-sm text-gray-500 dark:text-gray-400">
                    Marcá el inicio cuando empieces a trabajar
                </span>
            @endif
        </span>

        @if ($openEntry)
            <x-filament::button
                color="danger"
                wire:click="clockOut"
                wire:loading.attr="disabled"
            >
                Finalizar jornada
            </x-filament::button>
        @else
            <x-filament::button
                color="primary"
                wire:click="clockIn"
                wire:loading.attr="disabled"
            >
                Iniciar jornada
            </x-filament::button>
        @endif
    </div>
</x-filament-widgets::widget>
