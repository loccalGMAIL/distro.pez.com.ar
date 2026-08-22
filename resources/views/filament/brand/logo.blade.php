@php
    $brandName = \App\Models\CompanySetting::query()->value('razon_social') ?: filament()->getBrandName();
@endphp

<div class="flex flex-col justify-center leading-tight py-2">
    <span class="line-clamp-2 break-words text-[15.4px] font-bold">{{ $brandName }}</span>
    <span class="text-[11px] font-normal normal-case tracking-normal text-gray-400 dark:text-gray-500">
        v{{ config('app.version') }}
    </span>
</div>
