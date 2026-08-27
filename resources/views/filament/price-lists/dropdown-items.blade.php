@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\PriceList> $priceLists */
@endphp

<x-filament::dropdown.header icon="heroicon-o-document-arrow-down">
    ¿Qué lista querés compartir?
</x-filament::dropdown.header>

<x-filament::dropdown.list>
    @foreach ($priceLists as $priceList)
        <x-filament::dropdown.list.item
            tag="a"
            :href="route('price-lists.pdf', $priceList)"
            target="_blank"
        >
            {{ $priceList->nombre }}
        </x-filament::dropdown.list.item>
    @endforeach
</x-filament::dropdown.list>
