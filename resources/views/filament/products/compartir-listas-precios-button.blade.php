@php
    $priceLists = \App\Models\PriceList::orderedForSharing();
@endphp

@if ($priceLists->isNotEmpty())
    <x-filament::dropdown placement="bottom-end">
        <x-slot name="trigger">
            <x-filament::icon-button
                icon="heroicon-o-share"
                label="Compartir lista de precios"
                tooltip="Compartir lista de precios"
            />
        </x-slot>

        @include('filament.price-lists.dropdown-items', ['priceLists' => $priceLists])
    </x-filament::dropdown>
@endif
