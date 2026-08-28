<x-filament-panels::page>
    @php $summary = $this->summaryRows(); @endphp

    @if ($summary->isNotEmpty())
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($summary as $row)
                <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $row['user']->name }}</div>
                    <div class="mt-1 flex items-baseline justify-between">
                        <span class="text-lg font-semibold text-gray-950 dark:text-white">{{ $row['hours'] }} hs</span>
                        <span class="text-lg font-semibold text-gray-950 dark:text-white">${{ number_format($row['pay'], 2, ',', '.') }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{ $this->table }}
</x-filament-panels::page>
