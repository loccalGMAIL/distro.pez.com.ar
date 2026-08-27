@php
    $priceLists = \App\Models\PriceList::orderedForDisplay();
@endphp

<x-filament::dropdown placement="bottom-start" class="ms-2 lg:hidden">
    <x-slot name="trigger">
        <x-filament::icon-button
            color="gray"
            icon="heroicon-o-share"
            icon-size="lg"
            label="Compartir lista de precios"
        />
    </x-slot>

    @if ($priceLists->isNotEmpty())
        @include('filament.price-lists.dropdown-items', ['priceLists' => $priceLists])
    @endif
</x-filament::dropdown>
